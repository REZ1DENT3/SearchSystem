<?php

namespace App;
use App\Model\Word;
use PHPixie\ORM\Model;

/**
 * Base controller
 *
 * @property-read \App\Pixie $pixie Pixie dependency container
 */
class Page extends \PHPixie\Controller
{
	
	protected $view;

    public function before()
    {
        $this->view = $this->pixie->haml->get('main');	
    }

    public function after()
    {
        $this->response->body = $this->view->render();
    }

    public function get_words($string)
    {
        $string = preg_replace('/[^а-я\w-\s]/iu', '', $string);
        $string = preg_replace('/\s+/', ' ', $string);
        $array = explode(' ', mb_strtoupper($string));
        foreach($array as &$el)
            $el = "'$el'";
        return $array;
    }

    public function get_indices($words)
    {
        $words_id = [];
        if (is_string($words)) {
            $words = $this->get_words($words);
            if (!count($words))
                return false;
            $words = $this->pixie->db->expr('(' . implode(',', $words) . ')');
            $words = $this->pixie->orm->get(Models::Word)
                ->where('value', 'in', $words)
                ->find_all()
                ->as_array(true);
            foreach($words as $word) {
                $words_id[] = $word->id;
            }
        }
        else if (is_array($words)) {
            $words_id = $words;
        }
        if (!count($words_id))
            return false;
        $words_id = $this->pixie->db->expr("(" . implode(',', $words_id) . ")");
        return $this->pixie->orm->get(Models::Indice)
            ->where('word_id', 'in', $words_id)
            ->find_all()
            ->as_array(true);
    }

}