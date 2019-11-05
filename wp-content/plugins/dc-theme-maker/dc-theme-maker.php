<?php
/*
 * Plugin Name: DC Theme Maker
 */


Class DcThemeMaker
{
    private $pages;
    private $posts;
    private $taxes;
    public function __construct()
    {
        try{
            $this->autoloader();
            $this->setGlobalVars();
            $this->setActions();
            $this->setClassProperties();
        } catch (Exception $e) { die($e); }
    }
    private function autoloader()
    {
        try{
            spl_autoload_register(function ($className) {
                $className = str_replace("\\", "/", $className);
                $file = __DIR__ . '/' . $className . '.php';
                if (file_exists($file) == true) {
                    include ($file);
                }
            });
        } catch (Exception $e) { die($e); }
    }

    private function setGlobalVars()
    {
        global $jsonParser, $dc;

        $jsonParser = new Helpers\JsonParser;
        $dc = new DC();
    }

    private function setActions()
    {
        add_action('init', array($this, 'init'));
        add_action('pre_get_posts', array($this, 'preGetPosts'));
        add_action('template_include', array($this, 'templateIncludePosts'));
    }

    private function setClassProperties()
    {
        $this->pages = new Builders\Page();
        $this->posts = new Builders\Post();
        $this->taxes = new Builders\Taxonomy;
    }

    public function init()
    {   
        $this->pages->startRegPages();
        $this->posts->startRegPosts();
        $this->taxes->startRegTaxes();
    }
    public function preGetPosts($query)
    {
        $this->posts->setArchiveAsPage($query);
    }

    public function templateIncludePosts($template)
    {
        return $this->posts->setArchiveTemplate($template);
    }
    
}

new DcThemeMaker();