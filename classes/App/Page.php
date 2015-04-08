<?php

namespace App;

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
        $c = $this->request->server('HTTP_HOST');
        $this->view->web = ($c == 'searchsystem') ? 'web/' : '';
    }

    public function after()
    {
        $this->response->body = $this->view->render();
    }

}