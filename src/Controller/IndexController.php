<?php

namespace App\Controller;

use App\Repository\TextRepository;
use App\Domain\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class IndexController extends AbstractController
{

    private function get_current_text($conn) {
        $sql = "select TxID, TxTitle from texts
           where txid = (
             select StValue from settings where StKey = 'currenttext'
           )";
        $rec = $conn
             ->executeQuery($sql)
             ->fetchNumeric();
        if (! $rec)
            return [null, null];
        else
            return array_values($rec);
    }


    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine): Response
    {

        $error = '';
        $connect_inc = __DIR__ . '/../../connect.inc.php';
        if (!file_exists($connect_inc)) {
            $error = "Cannot find file: connect.inc.php." .
                   " Please create the file from connect.inc.php.example.";
        }
        elseif (!Parser::load_local_infile_enabled()) {
            $error = "SELECT @@GLOBAL.local_infile must be 1, check your mysql configuration.";
        }

        if ($error != '') {
            return $this->render('index_error.html.twig', [
                'error' => $error,
            ]);
        }

        $serversoft = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        $apache = "Apache/?";
        if (substr($serversoft[0], 0, 7) == "Apache/") { 
            $apache = $serversoft[0]; 
        }
        $php = phpversion();

        // $conn = $repo->getEntityManager()->getConnection();
        $conn = $doctrine->getConnection();
        $mysql = $conn
               ->executeQuery("SELECT VERSION() as value")
               ->fetchNumeric()[0];

        // TODO: eventually, get rid of this config file. :-)
        require $connect_inc;

        [ $txid, $txtitle ] = $this->get_current_text($conn);

        return $this->render('index.html.twig', [
            'serversoft' => $serversoft,
            'apache' => $apache,
            'php' => $php,
            'mysql' => $mysql,
            'dbname' => $dbname,
            'server' => $server,
            'symfconn' => $_ENV['DATABASE_URL'],
            'webhost' => $_SERVER['HTTP_HOST'],
            'currtxid' => $txid,
            'currtxtitle' => $txtitle
        ]);
    }

}
