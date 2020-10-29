<?php

namespace App\Http\Controllers;

use App\Http\Database\DataStore;
use App\Http\Database\GetDataDB;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public $GetDataDB;

    public function __construct()
    {
        $this->GetDataDB = new GetDataDB();
    }


    /**
     * Get the monthly measurements for a user
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function GetMonthlyMeasurements(Request $request, $type)
    {
        $values = null;


        if ($type == 'all') {
            $values = $this->GetDataDB->GetAllData($request);
        } else if ($type == 'hot' || $type == 'cold') {
            $values = $this->GetDataDB->GetDataUser($request, $type);
        }

        // find frem til sidste dato i måneden og vælg den seneste værdi og smid over i nyt array
        $onePerMonth = [];

        foreach ($values as $i => $v) {
            array_push($onePerMonth, $v->date->format('Y-m-t')); // finder sidste dag i måneden for en given dato
        }

        // fjern alle duplikater så der kun eksisterer de datoer der er nødvendige
        $onePerMonthStriped = array_unique($onePerMonth);

        $monthlyMeasurements = [];
        foreach ($onePerMonthStriped as $i => $o) { // for hver dato
            foreach ($values as $v) { // for hver datasæt
                // hvis datoen passer overens tilføjes dataen til arrayet
                // den overrider samme plads indtil der ikke er flere ved samme dato (aka den får den sidste måling for den data)
                if ($o == $v->date->format('Y-m-d')) {
                    $monthlyMeasurements[$i] = $v;
                }
            }
        }


        return $monthlyMeasurements;
    }

    /**
     * Get the actual consumption per month for a user
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function GetMonthlyConsumption(Request $request, $type, $returnType)
    {
        $monthlyMeasurements = $this->GetMonthlyMeasurements($request, $type);
        // lav nyt array som tager differencen
        $actualConsumption = [];
        $startValue = $monthlyMeasurements[0]->value; // gemmer den værdi måleren stod på efter første måling ..

        foreach ($monthlyMeasurements as $i => $data) {
            $newObject = new DataStore;
            $newObject->date = $data->date;
            $newObject->value = $data->value - $startValue; // tager målingen og minusser med sidste måneds måling for at få det faktiske forbrug
            $newObject->type = $data->type;
            $startValue = $data->value; // sætter startValue til at være dette måneds værdi så den kan bruges i næste iteration

            $actualConsumption[$i] = $newObject;
        }
        if ($returnType == 'list') {
            return $actualConsumption;
        } else if ($returnType == 'json') {
            return response()->json([
                $actualConsumption]);
        }

    }

    /**
     * Get the average consumption of a user
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function GetMonthlyAverage(Request $request)
    {
        $returnType = 'list';
        $type = 'all';
        $actualConsumption = $this->GetMonthlyConsumption($request, $type, $returnType);

        // find gennemsnit af forbrug
        $average = 0.0;

        foreach ($actualConsumption as $data) {
            $average += $data->value; // lægger alle tal sammen
        }

        $average = $average / (count($actualConsumption) - 1); // dividerer så vi får gennemsnit. -1 pga første datasæt altid er 0

        return $average;
    }


    // IKKE FÆRDIG

    // /**
    //  * Get the actual usage for the latests date
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function GetUsageToday(Request $request, $id)
    // {
    //     // få fat på den nyeste måling for i dag
    //     // få fat på seneste måling fra dagen før
    //     // træk tallene fra hinanden for at få dagens faktiske forbrug

    //     // $result = $this->GetDataUser($request, $id);

    //     // evt. gør det ved alle dage i en måned så man kan se hvilke dage man har brugt hvor meget

    //     // SQL query til at få fat på alle målinger for en bruger
    //     $result = DB::select('SELECT measurement, value FROM measurements
    //     WHERE deviceID = (
    //         SELECT deviceID FROM devices
    //         WHERE householdID = (
    //             SELECT householdID FROM households
    //             WHERE userID = ?
    //         )
    //         LIMIT 1, 1
    //     )', [$id]);

    //     // konverter resultatet til objekter og smid i et nyt array
    //     $values = [];

    //     foreach($result as $i => $m){
    //         //værdien
    //         // få fat i value string
    //         $valueString = $m->value;

    //         // fjern sidste 3 karakterer
    //         $string = substr($valueString, 0, strlen($valueString) - 3);

    //         // konverter til int
    //         $value = (double)$string;

    //         //datoen
    //         $dateString = $m->measurement;
    //         $dateString = substr($dateString, 0, 10);

    //         // opret objekt
    //         $data = new DataStore;
    //         $data->date = new DateTime($dateString);
    //         // $date = date_format($date 'Y-m-d H:i:s');
    //         $data->value = $value;

    //         // tilføj objekt til array
    //         $values[$i] = $data;
    //     }


    //     return "penis balls";
    //     // return $result;
    // }


    // ideer til andre
    // samlet månedlig forbrug
    // denne måneds varm forbrug
    // denne måneds kold forbrug


    /**
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function GetLatestMonth(Request $request, $type)
    {
        // Får enten alle varmt eller koldt vands målinger for en bruger
        $result = $this->GetDataDB->GetDataUser($request, $type);

        // Får fat i seneste måling
        $latestMeasurement = $this->GetLastItem($result);

        $i = count($result) - 1;
        $lastMonthsMeasurement = null;
        $found = false;

        // Starter fra bunden af listen og looper indtil der findes den første måling på listen som ikke har samme årstal og måned
        // som den seneste måling
        while (!$found) {
            if (date_format($result[$i]->date, 'Y-m') != date_format($latestMeasurement->date, 'Y-m')) {
                $lastMonthsMeasurement = $result[$i];
                $found = true;
            }
            $i--;
        }

        // udregn forbrug
        $actualUsageThisMonth = $latestMeasurement->value - $lastMonthsMeasurement->value;


        return $actualUsageThisMonth;
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function GetLatestMonthTotal(Request $request)
    {
        $hotWater = $this->GetLatestMonth($request, $type = 'hot');
        $coldWater = $this->GetLatestMonth($request, $type = 'cold');

        return $hotWater + $coldWater;
    }

    /**
     * @param $result
     * @return mixed
     */
    public function GetLastItem($result)
    {
        return array_pop($result);
    }

    /**
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function GetLatestYear(Request $request, $type)
    {
        // Får enten alle varmt eller koldt vands målinger for en bruger
        $result = $this->GetDataDB->GetDataUser($request, $type);

        // Får fat i seneste måling
        $latestMeasurement = $this->GetLastItem($result);

        $i = count($result) - 1;
        $lastYearsMeasurement = null;
        $found = false;

        // Starter fra bunden af listen og looper indtil der findes den første måling på listen som ikke har samme årstal som den seneste måling
        while (!$found) {
            if (date_format($result[$i]->date, 'Y') != date_format($latestMeasurement->date, 'Y')) {

                $lastYearsMeasurement = $result[$i];
                $found = true;
            }
            $i--;
        }

        // // udregner forbrug
        $actualUsageThisYear = $latestMeasurement->value - $lastYearsMeasurement->value;


        return $actualUsageThisYear;
    }

    /**
     * @param Request $request
     * @return float|\Illuminate\Http\Response
     */
    public function GetLatestYearTotal(Request $request)
    {
        $hotWater = $this->GetLatestYear($request, $type = 'hot');
        $coldWater = $this->GetLatestYear($request, $type = 'cold');

        return $hotWater + $coldWater;
    }


    /**
     * @param Request $request
     * @return int
     */
    public function GetMonthNumber(Request $request)
    {
        // Henter målinger for både koldt og varmt vand
        $result = $this->GetDataDB->GetAllData($request);

        // Får fat i den sidste måling
        $latestMeasurement = array_pop($result);

        //Får fat i datoen med formatet m. Formatet m returner en,
        // numerisk repræsentationen af måneden og konverter det til en int.
        $date = (int)date_format($latestMeasurement->date, 'm');

        return $date;
    }

    public function MonthlyUsageInDkk(Request $request)
    {
        $monthlyMeasurements = $this->GetMonthlyConsumption($request, $type = 'all', $returnType= 'list');
        $regionStore = $this->GetDataDB->GetPricePrCubic($request);
        $UsageInDkkList = [];


        foreach ($monthlyMeasurements as $m) {
            $usageInDkk = new UsageInDkk();
            $usageInDkk->price = substr($m->value * $regionStore->pricePrCubic, 0, -2);
            $usageInDkk->date = $m->date;

            array_push($UsageInDkkList, $usageInDkk);
        }
        return response()->json([
            $UsageInDkkList]);
    }
}

class UsageInDkk
{
    public $price;
    public $date;
}

