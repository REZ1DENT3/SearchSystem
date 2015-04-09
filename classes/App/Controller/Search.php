<?php

namespace App\Controller;

use App\Models;

class Search extends \App\Page
{
	
	public function action_index()
	{
        $t1 = xdebug_time_index();
        $q = '';
        if ($this->request->get('q') != null) {
            $q = $this->request->get('q');
        }
        $table = [];
        if ($this->request->get('table') != null) {
            $table = $this->request->get('table');
            if (strlen($table)) {
                $table = explode(',', $table);
            }
            else {
                $table = [];
            }
        }
        $this->view->select = count($table) ? current($table) : 'All';
        $se = new \App\SearchEngine($this->pixie);
        $this->view->rows = $se->get_rows($q, $table);
        $t2 = xdebug_time_index();
        $this->view->time = $t2 - $t1;
        $this->view->q = $q;
	}

    public function action_indices()
    {
        $t1 = xdebug_time_index();
        $se = new \App\SearchEngine($this->pixie);
        $se->indices(Models::Page, ['content', 'title']);
        $t2 = xdebug_time_index();
        die($t2 - $t1);
    }

    public function action_indices_tests()
    {
        $t1 = xdebug_time_index();
        $se = new \App\SearchEngine($this->pixie);
        $se->indices(Models::Test, ['value']);
        $t2 = xdebug_time_index();
        die($t2 - $t1);
    }

    function get_http_response_code($url) {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

    public function action_habra_parser()
    {
        $t1 = xdebug_time_index();
        include_once $this->pixie->root_dir . 'classes/App/simple_html_dom.php';
        $range = [254277, 253973, 244069, 219475, 216107, 205710, 197970, 194470, 189360, 188666, 186816, 186194, 178899, 178833];
        $range = array_merge($range, range(1, 254949));
        $url =
            $this->pixie->db->get()->execute("SELECT `url` FROM `pages` ORDER BY `id` DESC LIMIT 1")->as_array();
        if (count($url)) {
            $url = $url[0]->url;
            $key = array_search($url, $range);
            if ($key >= 0) {
                $range = array_splice($range, $key + 1);
            }
        }
        foreach($range as $r) {
            $url = "http://habrahabr.ru/post/$r/";
            if($this->get_http_response_code($url) != "200"){
                continue;
            }
            $content = file_get_html($url);
            $title = $content->find('h1.title span.post_title', 0);
            if (!$title) continue;
            $title = $title->plaintext;
            $content = $content->find('div.content', 0);
            if (!$content) continue;
            $page = $this->pixie->orm->get(Models::Page);
            $page->content = $content->plaintext;
            $page->title = $title;
            $page->datetime = date('Y-m-d H:i:s', time());
            $page->url = $r;
            $page->save();
        }
        $t2 = xdebug_time_index();
        die($t2 - $t1);
    }

}