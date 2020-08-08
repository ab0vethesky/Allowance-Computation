<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AllowanceController extends AbstractController
{
    /**
     * @Route("/allowance", name="allowance")
     */
    public function index()
    {
        return $this->render('allowance/index.html.twig', [
            'controller_name' => 'AllowanceController',
        ]);
    }
}
