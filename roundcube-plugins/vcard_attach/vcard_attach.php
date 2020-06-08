<?php
    class vcard_attach extends rcube_plugin
	{
        var $task = 'mail|settings|addressbook';
        private $vcard;
        private $plugin = 'vcard_attach';
        private $prefs = array('attach_vcard', 'attach_vcard_from', );
        private $config_dist = null;

        function init() {
            $rcmail = rcmail::get_instance();
            if ($rcmail->config->get('attach_vcard')) {
                $this->add_hook('message_compose', array($this, 'message_compose'));
                $this->add_hook('message_outgoing_body', array($this, 'create_vcard'));
                $this->add_hook('message_sent', array($this, 'unlink_vcard'));
            }
            if ($rcmail->task == 'addressbook' && ($rcmail->action == 'save' || rcube_utils::get_input_value('_source', rcube_utils::INPUT_GET))) {
                $this->add_hook('render_page', array($this, 'render_page'));
                $this->add_hook('send_page', array($this, 'send_page'));
            }
            if ($rcmail->task == 'settings') {
                $dont_override = $rcmail->config->get('dont_override', array());
                if (!in_array('attach_vcard', $dont_override)) {
                    $this->add_texts('localization/');
                    $this->add_hook('preferences_list', array($this, 'prefs_table'));
                    $this->add_hook('preferences_save', array($this, 'save_prefs'));
                }
            }
        }

        function send_page($args) {
            if (!$this->template && rcube_utils::get_input_value('_orig_source', rcube_utils::INPUT_GET)) {
                $script = html::tag('script', array('type' => 'text/javascript'), 'if(self.location==parent.location){window.onerror=function(){try{opener.parent.document.getElementById("preferences-frame").src="./?_action=edit-prefs&_task=settings&_section=compose&_framed=1";}catch(e){};self.close();}};');
                $args['content'] = str_replace('</title>', '</title>'.$script, $args['content']);
            }
            return $args;
        }

        function render_page($args) {
            $this->template = $args['template'];
            if ($args['template'] == 'contact') {
                $script = 'if(self.location==parent.location){function vcard_button(){return "<input type=\'button\' class=\'button\' onclick=\'self.close()\' value=\''.$this->gettext('close').
                '\' />"};window.onerror=function(){if(jQuery){window.setTimeout("$(\'.button\').prop(\'disabled\', false);$(\'#headerbuttons\').append(vcard_button());",100);opener.parent.document.getElementById("preferences-frame").src="./?_action=edit-prefs&_task=settings&_section=compose&_framed=1";} else {self.close()}}};';
                $script = html::tag('script', array('type' => 'text/javascript'), $script);
                $args['content'] = str_replace('</title>', '</title>'.$script, $args['content']);
            } else if ($args['template'] == 'contactedit' || $args['template'] == 'contactadd') {
                if (rcube_utils::get_input_value('_popup', rcube_utils::INPUT_GET)) {
                    $rcmail = rcmail::get_instance();
                    $script = '$(".button").each(function(){if($(this).attr("onclick") == "history.back()"){$(this).attr("onclick", "self.close()");}});var temp=document.location.search.split("&_create=");$($(".contactfieldcontent").children().get(0)).prop("readonly", true);if(temp[1]){$($(".contactfieldcontent").children().get(0)).val(temp[1]);}$($(".deletebutton").get(0)).remove();';
                    $rcmail->output->add_script($script, 'docready');
                }
            } else if ($args['template'] == 'addressbook' && rcube_utils::get_input_value('_source', RCUBE_INPUT_GPC) == 'collected') {
                $script = 'if(self.location==parent.location){try{opener.parent.document.getElementById("preferences-frame").src="./?_action=edit-prefs&_task=settings&_section=compose&_framed=1";}catch(e){};self.close()};';
                $script = html::tag('script', array('type' => 'text/javascript'), $script);
                $args['content'] = str_replace('<body', $script, $args['content'].
                    '<body');
            }
            return $args;
        }

        function prefs_table($args) {
            if ($args['section'] == 'compose') {
                $this->add_texts('localization');
                $this->include_script('vcard_attach.js');
                $rcmail = rcmail::get_instance();
                $attach_vcard = $rcmail->config->get('attach_vcard');
                $field_id = 'rcmfd_attach_vcard';
                $checkbox = new html_checkbox(array('name' => '_attach_vcard', 'id' => $field_id, 'value' => 1, 'onclick' => '$(".mainaction").hide(); document.forms.form.submit()'));
                $content = $checkbox->show($attach_vcard ? 1 : 0);
                $args['blocks']['main']['options']['attach_vcard'] = array('title' => html::label($field_id, rcube::Q($this->gettext('attachvcard'))), 'content' => $content, );
                if ($attach_vcard) {
                    $field_id = 'rcmfd_attach_vcard_from';
                    $select = new html_select(array('name' => '_attach_vcard_from', 'id' => $field_id));
                    $select->add($this->gettext('identities'), 0);
                    $select->add($this->gettext('contacts'), 1);
                    $content = $select->show((int) $rcmail->config->get('attach_vcard_from', 0), array('style' => 'margin-left: -6px', 'name' => "_attach_vcard_from", 'onchange' => 'if(this.value == 1){alert(\''.$this->gettext('vcard_attach.warning').'\');} $(\'.mainaction\').hide(); document.forms.form.submit();'));
                    $append = '';
                    if ($rcmail->config->get('attach_vcard_from') == 1) {
                        $identities = $rcmail->user->list_identities();
                        $sidentities = array();
                        foreach($identities as$key => $identity) {
                            $sidentities[$identity['email']][] = $identity;
                        }
                        ksort($sidentities);
                        $li = '';
                        foreach($sidentities as$key => $identity) {
                            $search = $identity[0]['email'];
                            $book_types = (array) $rcmail->config->get('autocomplete_addressbooks', 'sql');
                            $id = '';
                            $break = false;
                            $vcard_row = array();
                            if (!empty($book_types) && strlen($search)) {
                                foreach($book_types as$id) {
                                    $abook = $rcmail->get_address_book($id);
                                    if ($result = $abook->search(array('email'), $search, true, true, true, 'email')) {
                                        while ($sql_arr = $result->iterate()) {
                                            $vcard_row = (array) $abook->get_col_values('ID', $sql_arr, true);
                                            $break = true;
                                            break;
                                        }
                                    }
                                    if ($break)
									break;
                                }
                            }
                            $cid = $vcard_row[0];
                            $append = '';
                            $action = 'edit';
                            if (!$cid) {
                                $id = $rcmail->config->get('address_book_type');
                                $action = 'add';
                                $append = '&_create='.$identity[0]['email'];
                                $cid = 0;
                            }
                            $source = $id;
                            $li.= html::tag('li', null, html::tag('a', array('href' => '#', 'onclick' => 'opencontactwindow("./?_task=addressbook&_action='.$action.'&_source='.$source.'&_cid='.$cid.'&_popup=1'.$append.'")'), $identity[0]['email']));
                        }
                        $ul = html::tag('ul', array('style' => 'list-style: decimal; margin-top: -10px; margin-left: -25px; margin-bottom: 0;'), $li);
                        $append = html::tag('fieldset', array('id' => 'vcard_attach_contacts', 'style' => 'font-size: 11px; padding: 15px; margin-top: -7px; border: 1px solid #ABABAB; display: inline'), html::tag('legend', array('style' => 'font-size: 12px; padding-bottom: 0;'), $this->gettext('editcontact')).$ul);
                    }
                    $args['blocks']['main']['options']['attach_vcard_from'] = array('title' => '<!-- --><br />&raquo;&nbsp;'.html::label($field_id, rcube::Q($this->gettext('vcard_attach.from'))), 'content' => html::tag('table', array('cellpadding' => '0', 'cellspacing' => '0'), html::tag('tr', null, html::tag('td', array('style' => 'width: 1px;'), $content).html::tag('td', null, $append))), );
                }
            }
            return $args;
        }

        function save_prefs($args) {
            if ($args['section'] == 'compose') {
                $args['prefs']['attach_vcard'] = rcube_utils::get_input_value('_attach_vcard', rcube_utils::INPUT_POST);
                $args['prefs']['attach_vcard_from'] = rcube_utils::get_input_value('_attach_vcard_from', rcube_utils::INPUT_POST);
                return $args;
            }
        }

        function message_compose($args) {
            if ($file = $this->create_vcard_dummy()) {
                $args['attachments'][] = array('path' => $file, 'name' => "vcard.vcf", 'mimetype' => "text/vcard");
            }
            return $args;
        }

        function unlink_vcard($args) {
            $rcmail = rcmail::get_instance();
            $temp_dir = slashify($rcmail->config->get('temp_dir', 'temp'));
            $file = $temp_dir.md5($_SESSION['username']).
            ".vcf";
            if (file_exists($file)) {
                @unlink($file);
            }
            if (class_exists('database_attachments')) {
                $sql_result = $rcmail->db->query("DELETE FROM ".get_table_name('cache')."WHERE user_id = ? AND cache_key = ? ",$rcmail->user->ID,$this->vcard);
			}
			return $args;
		}
		function create_vcard($args){
			$rcmail = rcmail::get_instance();
			$temp_dir = slashify($rcmail->config->get('temp_dir','temp'));
			$file = $temp_dir.md5($_SESSION['username']).".vcf ";
			if(file_exists($file)){$content = "";
			$identities = $rcmail->user->list_identities();
			foreach($identities as$key => $identity){
				$iid = rcube_utils::get_input_value('_from',rcube_utils::INPUT_POST);
				$break = false;
				if($identity['identity_id'] == $iid){
					if($rcmail->config->get('attach_vcard_from',0)==1){$search = $identity['email'];
					$book_types = (array)$rcmail->config->get('autocomplete_addressbooks','sql');
					if(!empty($book_types)&&strlen($search)){
						foreach($book_types as$id){
							$abook = $rcmail->get_address_book($id);
							if($result = $abook->search(array('email'),$search,true,true,true,'email')){
								while($sql_arr = $result->iterate()){
									$vcard_arr = (array)$abook->get_col_values('vcard',$sql_arr,true);
									$break = true;
									break;
								}
							}
							if($break)
							break;
						}
					}
					$abook_vcard = $vcard_arr[0];
				}
				foreach($identity as$idx => $val){
					$identity[$idx] = utf8_decode($val);
				}
				$vcard = new rcube_vcard();
				$vcard->set('displayname',$identity['name']);
				if(!empty($identity['organization'])){$vcard->set('organization',$identity['organization']);
			}
			$temparr = array();
			if(!empty($identity['reply-to'])&&strtolower($identity['reply-to'])!= strtolower($identity['email']))$temparr[] = $identity['reply-to'];
			$temparr[] = $identity['email'];
			$vcard->set('email',$temparr);
			$temp = str_replace("\\ ; "," ; ",$vcard->export());
			$temp = str_replace("\\ : "," : ",$temp);
			$temparr = rcube_vcard::import($temp);
			if(is_array($temparr)){
				foreach($temparr[0]->email as$key => $val){
					if(is_array($val)){
						$count = count($val);
						$i = 0;
						foreach($val as$key1 => $email){
							$i++;
							if($email != ""&&$i<$count){
								$temp = str_replace($email.";"," ",$temp);
								$temp = str_replace("END: VCARD ","EMAIL; type = INTERNET;: ".$email."\nEND: VCARD ",$temp);
							}
						}
					}
				}
			}
			break;
		}
	}
	if($abook_vcard){
		$vcard = $abook_vcard;
	} else {
		$vcard = $temp;
	}
	$vcard = str_replace("N: ;;;;\ n "," ",$vcard);
	if(class_exists('database_attachments')){
		$id = rcube_utils::get_input_value('_id',rcube_utils::INPUT_POST);
		$data = $_SESSION['compose_data_'.$id];
		$attachments = $data['attachments'];
		foreach($attachments as$key => $attachment){$temparr = explode('/',$file);
		if($temparr[count($temparr)-1]==md5($_SESSION['username']).".vcf "){
			$sql_result = $rcmail->db->query("UPDATE ".get_table_name('cache')."SET data = ? WHERE cache_key = ? AND user_id = ? ",base64_encode($vcard),'db_attach.'.$attachment['id'],$rcmail->user->ID);
			$this->vcard = 'db_attach.'.$attachment['id'];
			break;
		}
	}
	} else {
		@file_put_contents($file,$vcard);
	}
	}
	return $args;
	}
	function create_vcard_dummy(){
		$rcmail = rcmail::get_instance();
		if($rcmail->config->get('attach_vcard')){
			$temp_dir = slashify($rcmail->config->get('temp_dir','temp'));
			$file = $temp_dir.md5($_SESSION['username']).".vcf ";
			if(file_put_contents($file," ")){
				return $file;
			}
		}
		return false;
	}
}