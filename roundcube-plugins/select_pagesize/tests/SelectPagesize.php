<?php

class Config
{
    function get()
    {
        return [10, 20, 50];
    }
}

class SelectPagesize extends \PHPUnit\Framework\TestCase
{
    private $plugin;

    function setUp()
    {
        include_once __DIR__ . '/../select_pagesize.php';
        $rcube  = rcube::get_instance();
        $this->plugin = new select_pagesize($rcube->api);
    }

    /**
     * Plugin object construction test
     */
    public function test_constructor()
    {
        $this->assertInstanceOf('select_pagesize', $this->plugin);
        $this->assertInstanceOf('rcube_plugin', $this->plugin);
    }

    /**
     * preferences_save method test
     */
    public function test_preferences_save()
    {
        $config = new Config();
        $this->plugin->set_config($config);

        $args = $this->plugin->preferences_save(['prefs' => ['mail_pagesize' => 50]]);
        $this->assertEquals(50, $args['prefs']['mail_pagesize']);

        $args = $this->plugin->preferences_save(['prefs' => ['mail_pagesize' => 40]]);
        $this->assertEquals(10, $args['prefs']['mail_pagesize']);
    }
}
