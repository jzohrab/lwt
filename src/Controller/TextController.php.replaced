<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

require_once __DIR__ . '/../../inc/database_connect.php';

class TextController extends AbstractController
{

    private function get_records($archived = 'false') : Array
    {
        global $DBCONNECTION;
        $sql = "select TxID, TxTitle from texts where TxArchived is {$archived}";
        $stmt = $DBCONNECTION->prepare($sql);
        $stmt->execute();
        $resultSet = $stmt->get_result();
        return $resultSet->fetch_all(MYSQLI_ASSOC);
    }

    #[Route('/text/active')]
    public function active(): Response
    {
        $texts = $this->get_records();
        return $this->render('text/list.html.twig', [
            'texts' => $texts,
        ]);
    }

    #[Route('/text/archived')]
    public function archived(): Response
    {
        $texts = $this->get_records('true');
        return $this->render('text/list.html.twig', [
            'texts' => $texts,
        ]);
    }

}