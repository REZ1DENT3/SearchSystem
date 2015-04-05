<?php

namespace App;

class SearchEngine
{
    /**
     * @var
     * @property-read \App\Pixie $pixie Pixie
     */
    protected $pixie;

    /**
     * @property-read \App\phpMorphy\phpMorphy
     */
    protected $morphy;

    /**
     * @param $pixie
     */
    public function __construct($pixie)
    {
        set_time_limit(0);
        $this->pixie = $pixie;
        $this->morphy = new phpMorphy\phpMorphy();
    }

    public function add_word($word)
    {
        $obj = $this->pixie->orm->get(Models::Word)
            ->where('value', $word)
            ->find();
        if ($obj->loaded()) {
            return $obj->id;
        }
        $obj->value = $word;
        if (!$obj->save())
            return false;
        return $obj->id;
    }

    /**
     * @param array $words
     * @return array
     */
    public function get_rating_reps($words)
    {
        $words = array_count_values($words);
        arsort($words);
        return $words;
    }

    /**
     * @param string $string
     * @return array
     */
    public function get_rows($string)
    {
        $rows = [];
        foreach($this->get_indices($string) as $k => $row) {
            $table = $this->pixie->orm->get(Models::Table, $row->table_id);
            if ($table->loaded()) {
                $row->table_name = $table->value;
                $table = $this->pixie->orm->get($row->table_name, $row->table_index);
                if ($table->loaded()) {
                    $rows[$k] = $table->as_array(true);
                }
            }
        }
        return $rows;
    }

    /**
     * @param string $string
     * @param string $expr
     * @return array
     */
    public function get_words($string)
    {
        $string = strip_tags($string);
        $string = preg_replace('/[^а-я\w-\s]/iu', '', $string);
        $string = preg_replace('/\s-\s/', ' ', $string);
        $string = preg_replace('/ё/iu', 'е', $string);
        $string = preg_replace('/\s+/', " ", $string);
        $string = mb_strtoupper($string);
        $array = explode(' ', $string);
        foreach($array as $key => $word) {
            if (empty($word)) {
                unset($array[$key]);
                continue;
            }
            $word = $this->morphy->ru->getBaseForm($word);
            if ($word) {
                $array[$key] = current($word);
            }
        }

        return $array;
    }

    /**
     * @param array $words
     * @return array
     */
    public function get_word_ids($words)
    {
        if (!count($words)) {
            return [];
        }

        foreach($words as &$word)
            $word = "'$word'";

        $words = $this->pixie->db->expr('(' . implode(',', $words) . ')');
        $words = $this->pixie->orm->get(Models::Word)
            ->where('value', 'in', $words)
            ->where('weight', '>', 0)
            ->find_all()
            ->as_array(true);

        $words_id = [];
        foreach($words as $word) {
            $words_id[] = $word->id;
        }

        return $words_id;
    }

    public function indices($model = \App\Models::Page, $columns = ['content'])
    {
        $m = $this->pixie->orm->get($model)
            ->find_all()
            ->as_array();
        $t = $this->pixie->orm->get(Models::Table)
            ->where('value', $model)
            ->find();
        if (!$t->loaded())
            return false;
        $t = $t->id;
        foreach($m as $p) {
            foreach($columns as $column) {
                $content = $p->{$column};
                $words = $this->get_words($content);
                $words = $this->get_rating_reps($words);
                foreach($words as $word => $reps) {
                    if ($id = $this->add_word($word)) {
                        $indice = $this->pixie->orm->get(Models::Indice)
                            ->where('word_id', $id)
                            ->where('table_id', $t)
                            ->where('table_index', $p->id)
                            ->find();
                        if ($indice->loaded())
                            continue;
                        $indice->word_id = $id;
                        $indice->table_id = $t;
                        $indice->table_index = $p->id;
                        $indice->rating_reps = $reps;
                        $indice->weight = $this->weight($word);
                        $indice->save();
                    }
                }
            }
        }
    }

    /**
     * @param string $words
     * @return array
     */
    public function get_indices($string)
    {
        $words = $this->get_words($string);
        $words_id = $this->get_word_ids($words);

        if (!count($words_id)) {
            return [];
        }

        $sql = "SELECT `table_id`, `table_index`
                FROM `indices`
                WHERE `word_id` in (" . implode(',', $words_id) . ")
                GROUP BY `table_index`, `table_id`
                ORDER BY `weight`, COUNT(`rating_reps`) ASC";

        $db = $this->pixie->db->get();
        return $db->execute($sql)->as_array();
    }

    public function weight($word)
    {

//        C=существительное
//        П=прилагательное
//        МС=местоимение-существительное
//        Г=глагол в личной форме
//        ПРИЧАСТИЕ=причастие
//        ДЕЕПРИЧАСТИЕ=деепричастие
//        ИНФИНИТИВ=инфинитив
//        МС-ПРЕДК=местоимение-предикатив
//        МС-П=местоименное прилагательное
//        ЧИСЛ=числительное (количественное)
//        ЧИСЛ-П=порядковое числительное
//        Н=наречие
//        ПРЕДК=предикатив
//        ПРЕДЛ=предлог
//        СОЮЗ=союз
//        МЕЖД=междометие
//        ЧАСТ=частица
//        ВВОДН=вводное слово

        $profile = [
            'ПРЕДЛ' => 0, 'СОЮЗ'  => 0,
            'МЕЖД'  => 0, 'ВВОДН' => 0,
            'ЧАСТ'  => 0, 'МС'    => 0,

            'С'     => 3, 'Г'     => 3,
            'П'     => 2, 'Н'     => 2
        ];

        $parts_of_speech = $this->morphy->ru->getPartOfSpeech($word);

        if (!$parts_of_speech)
            return 1;

        $range = [];
        foreach($parts_of_speech as $word => $speech) {
            if (is_array($speech)) {
                foreach ($speech as $ind => $val) {
                    if (isset($profile[$val])) {
                        $range[] = $profile[$val];
                    } else {
                        $range[] = 1;
                    }
                }
            }
            else {
                if (isset($profile[$speech])) {
                    $range[] = $profile[$speech];
                } else {
                    $range[] = 1;
                }
            }
        }

        return max($range);

    }
}