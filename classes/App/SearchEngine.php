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

    private $_tables_value = [];
    private $_tables_full_value = [];

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

    public function get_table($id, $full = false)
    {
        if ($full) {
            if (isset($this->_tables_full_value[$id])) {
                return $this->_tables_full_value[$id];
            }
        }
        if (isset($this->_tables_value[$id])) {
            return $this->_tables_value[$id];
        }

        $table = $this->pixie->orm->get(Models::Table, $id);
        if ($table->loaded()) {
            $this->_tables_full_value[$id] = $table->full_value;
            $this->_tables_value[$id] = $table->value;
            if ($full) {
                return $table->full_value;
            }
            return $table->value;
        }

        return false;
    }

    /**
     * @param string $string
     * @return array
     */
    public function get_rows($string, $tables = [])
    {
        $_rows = [];
        $indices = $this->get_indices($string, $tables);
        foreach($indices as $key => $row) {

            $table = $this->get_table($row->table_id);
            if ($table) {

                $_rows[$table][$key] = $row->table_index;
            }

        }

        $rows = [];
        foreach ($_rows as $table_name => $_row) {

            $ids = array_values($_row);

            foreach ($ids as &$id)
                $id = "'$id'";

            $ids = implode(',', $ids);
            $fields = $this->pixie->db->expr("FIELD(id, " . $ids .  " )");
            $ids = $this->pixie->db->expr('(' . $ids . ')');

            $__rows = $this->pixie->orm->get($table_name)
                ->where('id', 'IN', $ids)
                ->order_by($fields)
                ->find_all()
                ->as_array(true);

            $keys = array_keys($_row);

            foreach ($__rows as $row) {
                $key = current($keys);
                next($keys);
                $row->__table = $table_name;
                $rows[$key] = $row;
            }

        }

        ksort($rows);

        return $rows;
    }

    /**
     * @param string $string
     * @param string $expr
     * @return array
     */
    public function get_words($string)
    {

        // FIXME: Дополнительный пробел "перед" тегом, для <p>hello</p>world
        $string = preg_replace('/([<].*?[>])/', ' $1 ', $string);

        $string = strip_tags($string);
        $string = preg_replace('/[^а-я\w-\s]/iu', ' ', $string);
        $string = preg_replace('/[\s\t\n\r]-[\s\t\n\r]/', ' ', $string);
        $string = preg_replace('/ё/iu', 'Е', $string);
        $string = preg_replace('/[\s\t\n\r]+/', ' ', $string);
        $string = mb_strtoupper($string);

        if (empty($string)) {
            return [];
        }

        $array = explode(' ', $string);

        foreach($array as $key => $word) {

            $word = $this->morphy->get('ru')->getBaseForm($word);
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

        $_model = $this->pixie->orm->get($model)
            ->find_all()
            ->as_array();

        $_table = $this->pixie->orm->get(Models::Table)
            ->where('value', $model)
            ->find();

        if (!$_table->loaded())
            return false;

        $_table = $_table->id;

        foreach($_model as $_index) {

            foreach($columns as $column) {

                $content = $_index->{$column};
                $words = $this->get_words($content);
                $words = $this->get_rating_reps($words);

                foreach($words as $word => $reps) {

                    if ($word_id = $this->add_word($word)) {

                        $indice = $this->pixie->orm->get(Models::Indice)
                            ->where('word_id', $word_id)
                            ->where('table_id', $_table)
                            ->where('table_index', $_index->id)
                            ->find();

                        $indice->word_id = $word_id;
                        $indice->table_id = $_table;
                        $indice->table_index = $_index->id;
                        $indice->rating_reps = $reps;
                        $indice->weight = $this->weight($word);
                        $indice->save();

                    }
                }
            }
        }

        return true;
    }

    /**
     * @param string $words
     * @return array
     */
    public function get_indices($string, $tables = [])
    {
        $words = $this->get_words($string);
        $words_id = $this->get_word_ids($words);

        if (!count($words_id)) {
            return [];
        }

        $sql_tables = "";
        if (count($tables)) {

            foreach($tables as $k => &$table) {

                $table = $this->pixie->orm->get(Models::Table)
                    ->where('value', $table)
                    ->find();

                if ($table->loaded()) {
                    $table = "'{$table->id}'";
                }
                else {
                    unset($tables[$k]);
                }
            }

            if (count($tables)) {
                $sql_tables = "AND `table_id` IN (" . implode(', ', $tables) . ')';
            }
        }

        $sql = "SELECT `table_id`, `table_index`
                FROM `indices`
                WHERE `word_id` IN (" . implode(',', $words_id) . ") $sql_tables
                GROUP BY `table_index`, `table_id`
                ORDER BY `weight`, SUM(`rating_reps`) DESC";

        $db = $this->pixie->db->get();
        return $db->execute($sql)->as_array();

    }

    public function weight($word)
    {

        $profile = [

            'С'     => 3, 'Г'     => 3,
            'П'     => 2, 'Н'     => 2,

            'ПРЕДЛ' => 0, 'СОЮЗ'  => 0,
            'МЕЖД'  => 0, 'ВВОДН' => 0,
            'ЧАСТ'  => 0, 'МС'    => 0

        ];

        $parts_of_speech = $this->morphy->get('ru')->getPartOfSpeech($word);

        if (!$parts_of_speech)
            return 1;

        $range = [];
        $index = 0;
        foreach($parts_of_speech as $word => $speech) {

            $range[$index] = 1;
            if (is_array($speech)) {
                if (count($speech)) {

                    $temp_speech = [current($speech)];
                    while (next($speech)) {

                        $key = current($speech);
                        if (isset($profile[$key]))
                            $temp_speech[] = $profile[$key];
                    }
                    $range[$index] = max($temp_speech);
                }
            }
            else {
                if (isset($profile[$speech])) {
                    $range[$index] = $profile[$speech];
                }
            }
        }

        return max($range);

    }
}