<?php

namespace App\Controller;

use App\Models;

class Search extends \App\Page
{
	
	public function action_index()
	{
        $q = 'Привет, Хабр!';
        if ($this->request->get('q') != null) {
            $q = $this->request->get('q');
        }
        $rows = $this->get_indices($q);
        foreach($rows as $k => $row) {
            $table = $this->pixie->orm->get(Models::Table, $row->table_id);
            if ($table->loaded()) {
                $row->table_name = $table->value;
                $table = $this->pixie->orm->get($row->table_name, $row->table_index);
                if ($table->loaded()) {
                    $rows[$k] = $table->as_array(true);
                }
                else {
                    unset($rows[$k]);
                }
            }
            else {
                unset($rows[$k]);
            }
        }
        $this->view->rows = $rows;
	}
	
}