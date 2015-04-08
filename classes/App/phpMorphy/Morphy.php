<?php

namespace App\phpMorphy;

include_once 'src/common.php';

class Morphy extends \phpMorphy
{

    protected $_rows = [];

    /**
     * Alias for getBaseForm
     *
     * @param mixed $word - string or array of strings
     * @param mixed $type - prediction managment
     * @return array
     */
    function lemmatize($word, $type = self::NORMAL) {
        if (isset($this->_rows[$word][$type]))
            return $this->_rows[$word][$type];

        $this->_rows[$word][$type] = $this->getBaseForm($word, $type);
        return $this->_rows[$word][$type];
    }

    /**
     * @param mixed $word - string or array of strings
     * @param mixed $type - prediction managment
     * @return array
     */
    function getBaseForm($word, $type = self::NORMAL) {
        if (isset($this->_rows[$word][$type]))
            return $this->_rows[$word][$type];

        $this->_rows[$word][$type] = $this->invoke('getBaseForm', $word, $type);
        return $this->_rows[$word][$type];
    }

}