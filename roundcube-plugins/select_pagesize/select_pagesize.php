<?php

class select_pagesize extends rcube_plugin
{
    public $task = 'settings';
    private $config;
    private $output;

    function init()
    {
        $rcmail = rcube::get_instance();
        $this->set_config($rcmail->config);
        $this->set_output($rcmail->output);

        $pagesize_options = $this->config->get('pagesize_options');
        $this->output->set_env('pagesize_options', $pagesize_options);

        $this->add_hook('preferences_list', [$this, 'preferences_list']);
        $this->add_hook('preferences_save', [$this, 'preferences_save']);
    }

    function set_config($config)
    {
        $this->config = $config;
    }

    function set_output($output)
    {
        $this->output = $output;
    }

    function preferences_list($args)
    {
        if ($args['section'] === 'mailbox') {
//            if

            $rc = rcmail::get_instance();
            $prefs = $rc->user->get_prefs();
            $current = isset($prefs['mail_pagesize']) ? $prefs['mail_pagesize'] : null;

            $select = new html_select(['name' => '_mail_pagesize', 'id' => 'rcmfd_mail_pagesize']);

            foreach ($this->config->get('pagesize_options') as $size) {
                $select->add($size, $size);
            }

            $args['blocks']['main']['options']['pagesize'] = [
                'title' => $this->gettext('pagesize'),
                'content' => $select->show($current)
            ];
        }

        return $args;
    }

    function preferences_save($args)
    {
        if (!in_array((int)$args['prefs']['mail_pagesize'], $this->config->get('pagesize_options'))) {
            $args['prefs']['mail_pagesize'] = $this->config->get('pagesize_options')[0];
        }
        return $args;
    }
}
