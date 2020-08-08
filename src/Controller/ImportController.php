<?php

namespace App\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportController extends AbstractController
{

    public function import(Request $request)

    {
        $file = $request->files->get('csv_file');

        if (empty($file)) {

            return $this->render('/import/import.html.twig');
        }

        $tempDir = $this->getParameter('kernel.project_dir') . '\\var\\uploads\\';

        /*
            Create a temporary csv file and convert it into an array
        */
        $tempFileName = md5(uniqid()) . '.csv';

        $file->move($tempDir, $tempFileName);
        $fileFullPath = $tempDir . $tempFileName;
        chmod($fileFullPath, 0777);

        $file_data = fopen($fileFullPath, "r");

        $details = $this->convertToArray($file_data);

        $response = new Response();

        return $response;
    }

    /*
        Return an associative array including the computed columns using the imported csv file

    */
    public function convertToArray($file_data)
    {
        fgetcsv($file_data);

        $today = date("d-m-Y");

        while ($row = fgetcsv($file_data)) {

            $data[] = array(
                'Employee ID'  => $row[0],
                'Employee Name' => $row[1],
                'Transport Type' => $row[2],
                'Distance (km)'  => number_format($row[3], 2),
                'No of Workdays (per week)'  => number_format($row[4], 2),
                'Compensation (EUR)' => $this->computeAmt($row[2], $row[3], $row[4]),
                'Payment Date' => $today
            );
        }

        /*
            Create a new file and export the array as csv
        */
        $fileName = "export_" . date("Y_m_d_His") . ".csv";
        $this->printCsv($fileName, $data);

        return $data;
    }

        /*
            Function to print the csv as an array

        */
    public function printCsv($fileName, $assocDataArray)
    {
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $fileName);

        if (isset($assocDataArray['0'])) {
            $fp = fopen('php://output', 'w');
            fputcsv($fp, array_keys($assocDataArray['0']));

            foreach ($assocDataArray as $values) {
                fputcsv($fp, $values);
            }
            fclose($fp);
        }
        ob_flush();
    }

    /*
        Function to get return values from the endpoint
    */

    public function getApiData()
    {

        try {
            
            $content = file_get_contents('https://api.staging.yeshugo.com/applicant/travel_types');
            $result = json_decode($content, true);

            $res = (object)$result;

        } catch (\Exception $e) {
            throw new Exception("JSON fetch error! " . $e);
            
        }

        return $res;
    }

    /*
        Comppute the allowance amount using the csv data and values from the api

    */
    public function computeAmt($transType, $distVal, $wrkDays)
    {
        /*
        Get the data from the api

        */
        $resObject = $this->getApiData(); // 

        foreach ($resObject as $obj) {

            $objId = $obj['id'];

            switch ($objId) {
                case 1:
                    $train = $obj['base_compensation_per_km'];
                    break;
                case 2:
                    $car = $obj['base_compensation_per_km'];
                    break;
                case 3:
                    $bike = $obj['base_compensation_per_km'];
                    break;
                case 4:
                    $bus = $obj['base_compensation_per_km'];
                    break;
            }

            /*
                Compute the allowance amount based on the transport type     
                
                TODO - refactor this block
            */
            error_reporting(0);
            switch ($transType) {
                case "TRAIN":
                    $comp = $train;
                    $minkm = isset($obj['exceptons']['min_km']);
                    $maxkm = isset($obj['exceptons']['max_km']);
                    $factor = isset($obj['exceptions']['factor']);
                    $weekDays = $wrkDays;
                    $amt = $this->computeExcp($comp, $minkm, $maxkm, $distVal, $weekDays, $factor);
                    break;

                case "CAR":
                    $comp = $car;
                    $minkm = isset($obj['exceptons']['min_km']);
                    $maxkm = isset($obj['exceptons']['max_km']);
                    $factor = isset($obj['exceptions']['factor']);
                    $weekDays = $wrkDays;
                    $amt = $this->computeExcp($comp, $minkm, $maxkm, $distVal, $weekDays, $factor);
                    break;

                case "BIKE":
                    $comp = $bike;
                    $minkm = isset($obj['exceptons']['min_km']);
                    $maxkm = isset($obj['exceptons']['max_km']);
                    $factor = isset($obj['exceptions']['factor']);
                    $weekDays = $wrkDays;
                    $amt = $this->computeExcp($comp, $minkm, $maxkm, $distVal, $weekDays, $factor);

                    break;

                case "BUS":
                    $comp = $bus;
                    $minkm = isset($obj['exceptons']['min_km']);
                    $maxkm = isset($obj['exceptons']['max_km']);
                    $factor = isset($obj['exceptions']['factor']);
                    $weekDays = $wrkDays;
                    $amt = $this->computeExcp($comp, $minkm, $maxkm, $distVal, $weekDays, $factor);

                    break;
                default:
                    $amt = 0;
                    break;
            }
        }

        return number_format(($amt * 4), 2); // Multiply result by 4 to get monthly figure
    }

    /*
     
        Use a factor to compute allowance amount if distance traveled falls within exception range

     */
    public function computeExcp($comp, $minkm, $maxkm, $distVal, $weekDays, $factor)
    {
        if (($distVal >= $minkm) && ($distVal <= $maxkm)) {
            $amtExcp = $comp * $factor * $distVal * $weekDays * 2; // Multiply by 2 since distance traveled is one-way
        } else {
            $amtExcp = $comp * $distVal * $weekDays * 2;
        }
        return $amtExcp;
    }
}