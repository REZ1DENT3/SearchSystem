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

        $string = htmlspecialchars_decode($string);

        // FIXME: Дополнительный пробел "перед" тегом, для <p>hello</p>world
        $string = preg_replace('/([<].*?[>])/', ' $1 ', $string);

        $string = strip_tags($string);
        $string = preg_replace('/[^а-я\w-\s]/iu', ' ', $string);
        $string = preg_replace('/_/iu', ' ', $string);
        $string = preg_replace('/[\s\t\n\r]-[\s\t\n\r]/', ' ', $string);
        $string = preg_replace('/ё/iu', 'Е', $string);
        $string = preg_replace('/[\s\t\n\r]+/', ' ', $string);
        $string = preg_replace('/[-]{2,}/', '-', $string);
        $string = mb_strtoupper($string);

        if (empty($string)) {
            return [];
        }

        $array = explode(' ', $string);

        $count = count($array);
        for ($key = 0; $key < $count; $key++)
        {

            $word = $array[$key];
            if (is_numeric($word))
                continue;

            if ($word == '-') {
                unset($array[$key]);
                continue;
            }

            $_word = $this->morphy->get('ru')->getBaseForm($word);
            if ($_word) {
                $array[$key] = current($_word);
            }
            else {
                if (preg_match('/\d+/', $word)) {
                    unset($array[$key]);
                    continue;
                }
                $word = explode('-', $word);
                if (count($word) > 2) {
                    $array[$key] = current($word);
                    unset($word[0]);
                    foreach ($word as $__key => $__word) {
                        if (empty($word))
                            unset($word[$__key]);
                    }
                    $array = array_merge($array, $word);
                    $count = count($array);
                    $key--;
                }
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

    /**
     * Gets singular form of a noun
     *
     * @param string $str Noun to get singular form of
     * @return string Singular form of the noun
     */
    protected function singular($str)
    {
        $regexes = array(
            '/^(.*?us)$/i' => '\\1',
            '/^(.*?[sxz])es$/i' => '\\1',
            '/^(.*?[^aeioudgkprt]h)es$/i' => '\\1',
            '/^(.*?[^aeiou])ies$/i' => '\\1y',
            '/^(.*?)s$/' => '\\1',
        );
        foreach ($regexes as $key => $val)
        {
            $str = preg_replace($key, $val, $str, -1, $count);
            if ($count)
            {
                return $str;
            }
        }
        return $str;
    }

    /**
     * Gets plural form of a noun
     *
     * @param string  $str Noun to get a plural form of
     * @return string  Plural form
     */
    protected function plural($str)
    {
        $regexes = array(
            '/^(.*?[sxz])$/i' => '\\1es',
            '/^(.*?[^aeioudgkprt]h)$/i' => '\\1es',
            '/^(.*?[^aeiou])y$/i' => '\\1ies',
        );
        foreach ($regexes as $key => $val)
        {
            $str = preg_replace($key, $val, $str, -1, $count);
            if ($count)
            {
                return $str;
            }
        }
        return $str.'s';
    }

    public function indices($table_name = \App\Models::Page, $columns = ['content'])
    {

        $limit = 100;
        $offset = 0;
        $pred = $offset;

        while ($pred == $offset)
        {

            $_table = $this->pixie->orm->get(Models::Table)
                ->where('value', $table_name)
                ->find();

            if (!$_table->loaded())
                return false;

            $_table = $_table->id;

            /**
            SELECT SQL_NO_CACHE *
            FROM `pages`
            WHERE `id` NOT IN (
            SELECT DISTINCT `table_index`
            FROM `indices`
            WHERE `table_id`=2
            )
            LIMIT 0, 100

             * Quick
            SELECT SQL_NO_CACHE `pages`.*
            FROM `pages`
            LEFT JOIN (
            SELECT DISTINCT `table_index` `id`
            FROM `indices`
            WHERE `table_id`=2
            ) `ind` ON `ind`.`id`=`pages`.`id`
            WHERE `ind`.`id` IS NULL
            ORDER BY `pages`.`id` DESC
            LIMIT 0, 100
             */

            $indice_table = $this->plural(Models::Indice);
            $plural_table = $this->plural($table_name);

            $sql = "SELECT `$plural_table`.*
                    FROM `pages`
                      LEFT JOIN (
                        SELECT DISTINCT `table_index` `id`
                        FROM `$indice_table`
                        WHERE `table_id`=$_table
                      ) `ind` ON `ind`.`id`=`pages`.`id`
                    WHERE `ind`.`id` IS NULL
                    ORDER BY `$plural_table`.`id` ASC
                    LIMIT $offset, $limit";

            $offset += $limit;

            $db = $this->pixie->db->get();
            $_model = $db->execute($sql)->as_array();

//            $_model = $this->pixie->orm->get($table_name)
//                ->offset($offset * $i)
//                ->limit(100)
//                ->find_all()
//                ->as_array();

            $pred = count($_model);


            foreach ($_model as $_index) {

                foreach ($columns as $column) {

                    $content = $_index->{$column};
                    $words = $this->get_words($content);
                    $words = $this->get_rating_reps($words);

                    $loaded = $this->pixie->orm->get(Models::Indice)
                        ->where('table_id', $_table)
                        ->where('table_index', $_index->id)
                        ->find()
                        ->loaded();

                    if ($loaded)
                        continue;

                    foreach ($words as $word => $reps) {

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