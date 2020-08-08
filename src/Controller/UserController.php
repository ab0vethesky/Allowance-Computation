<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form;

class UserController extends AbstractController
{
    // ...

	public function import()
	{
		$userFirstName = '...';
        $userNotifications = ['...', '...'];
		
		return $this->render('user/import.html.twig',[
		'user_first_name' => $userFirstName,
            'notifications' => $userNotifications,
		]);
	}
	

    public function notifications()
    {
        // get the user information and notifications somehow
        $userFirstName = '...';
        $userNotifications = ['...', '...'];

        // the template path is the relative file path from `templates/`
        return $this->render('user/notifications.html.twig', [
            // this array defines the variables passed to the template,
            // where the key is the variable name and the value is the variable value
            // (Twig recommends using snake_case variable names: 'foo_bar' instead of 'fooBar')
            'user_first_name' => $userFirstName,
            'notifications' => $userNotifications,
        ]);
    }
	
}