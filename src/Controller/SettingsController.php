<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

require_once __DIR__ . '/../../inc/database_connect.php';

class SettingsController extends AbstractController
{

    #[Route('/settings/symfony')]
    public function symf_settings(): Response
    {
        return $this->render('settings/symfony.html.twig', [
            'DATABASE_URL_ENV' => $_ENV['DATABASE_URL'],
            'DATABASE_URL_SERVER' => $_SERVER['DATABASE_URL']
        ]);
    }

}