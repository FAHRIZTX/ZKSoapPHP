<?php

namespace Fahriztx\Zksoapphp;
/**
 * Fingerprint class
 */
class Fingerprint
{
    private static $conn;
    private static $ip;
    private static $port;
    private static $comkey;
    private static $isConnected = false;
    private static $NL = "\r\n";

    private static $payload = [
        'GetAttLog' => '<GetAttLog><ArgComKey xsi:type=\"xsd:integer\">#COMKEY</ArgComKey>#PIN</GetAttLog>',
        'GetUserInfo' => '<GetUserInfo><ArgComKey xsi:type=\"xsd:integer\">#COMKEY</ArgComKey>#PIN</GetUserInfo>'
    ];

    public static function connect($ip, $port=80, $comkey=0)
    {
        static::$ip = $ip;
        static::$port = $port;
        static::$comkey = $comkey;

        static::$conn = @fsockopen(static::$ip, static::$port, $errno, $errstr, 1);

        if (static::$conn) {
            static::$isConnected = true;
        }else{
            static::$isConnected = false;
        }
        
        return (new static);
    }

    public function getStatus()
    {
        return static::$isConnected ? 'connected' : 'disconnected';
    }

    public function getUserInfo($pin='all')
    {
        $payload = self::$payload['GetUserInfo'];

        static::connect(static::$ip, static::$port, static::$comkey);

        if (is_array($pin)) {
            $pinPayload = "";
            foreach ($pin as $value) {
                $pinPayload .= "<Arg><PIN>".$value."</PIN></Arg>";
            }
            $pin = $pinPayload;
        }else {
            $pin = "<Arg><PIN>".$pin."</PIN></Arg>";
        }

        $payload = str_replace("#PIN", $pin, $payload);

        $this->generateRequest($payload);

        $buffer = "";
        $isStartNow = false;
        while ($res = fgets(static::$conn, 1024)) {
            if (strpos($res, "<GetUserInfoResponse>") !== false) {
                $isStartNow = true;
            }
            if($isStartNow) {
                $buffer = $buffer.$res;
            }
        }

        $gaRes = ["<GetUserInfoResponse>", "</GetUserInfoResponse>", "\r\n"];
        $buffer = str_replace($gaRes, "", $buffer);

        fclose(static::$conn);
        return static::parseUserInfoData($buffer);
    }

    private static function parseUserInfoData($data="") {

        $dataRow = explode("<Row>", $data);
        array_shift($dataRow);

        $userData = [];

        foreach ($dataRow as $key => $value) {
            $endRow = explode("</Row>", $value)[0];

            $fid = static::getValueFromTag($endRow, "<PIN>", "</PIN>");
            $name = static::getValueFromTag($endRow, "<Name>", "</Name>");
            $password = static::getValueFromTag($endRow, "<Password>", "</Password>");
            $group = static::getValueFromTag($endRow, "<Group>", "</Group>");
            $privilege = static::getValueFromTag($endRow, "<Privilege>", "</Privilege>");
            $card = static::getValueFromTag($endRow, "<Card>", "</Card>");
            $pin2 = static::getValueFromTag($endRow, "<PIN2>", "</PIN2>");

            $userData[] = [
                'pin' => $fid,
                'name' => $name,
                'password' => $password,
                'group' => $group,
                'privilege' => $privilege,
                'card' => $card,
                'pin2' => $pin2,
            ];
        }

        return $userData;
    }

    public function getAttendance($pin='all', $date_start=null, $date_end=null)
    {
        if ($date_start != null && $date_end == null) {
            $date_end = $date_start;
        }

        $payload = self::$payload['GetAttLog'];

        static::connect(static::$ip, static::$port, static::$comkey);

        if (is_array($pin)) {
            $pinPayload = "";
            foreach ($pin as $value) {
                $pinPayload .= "<Arg><PIN>".$value."</PIN></Arg>";
            }
            $pin = $pinPayload;
        }else {
            $pin = "<Arg><PIN>".$pin."</PIN></Arg>";
        }

        $payload = str_replace("#PIN", $pin, $payload);

        $this->generateRequest($payload);

        $buffer = "";
        $isStartNow = false;
        while ($res = fgets(static::$conn, 1024)) {
            if (strpos($res, "<GetAttLogResponse>") !== false) {
                $isStartNow = true;
            }
            if($isStartNow) {
                $buffer = $buffer.$res;
            }
        }

        $gaRes = ["<GetAttLogResponse>", "</GetAttLogResponse>", "\r\n"];
        $buffer = str_replace($gaRes, "", $buffer);

        fclose(static::$conn);
        return static::parseAttendanceData($buffer, $date_start, $date_end);
    }

    private static function parseAttendanceData($data="", $date_start=null, $date_end=null) {
        $dataRow = explode("<Row>", $data);
        array_shift($dataRow);

        $fingerData = [];

        foreach ($dataRow as $key => $value) {
            $endRow = explode("</Row>", $value)[0];

            $fid = static::getValueFromTag($endRow, "<PIN>", "</PIN>");
            $datetime = static::getValueFromTag($endRow, "<DateTime>", "</DateTime>");
            $verified = static::getValueFromTag($endRow, "<Verified>", "</Verified>");
            $status = static::getValueFromTag($endRow, "<Status>", "</Status>");
            $workcode = static::getValueFromTag($endRow, "<WorkCode>", "</WorkCode>");

            if ($date_start != null &&  $date_end != null) {
                
                $rangeDate = static::dateRange($date_start, $date_end);

                $dateCheck = explode(' ', $datetime)[0];

                if (in_array($dateCheck, $rangeDate)) {
                    $fingerData[] = [
                        'pin' => $fid,
                        'datetime' => $datetime,
                        'verified' => $verified,
                        'status' => $status,
                        'workcode' => $workcode,
                    ];
                }

            } else {
                $fingerData[] = [
                    'pin' => $fid,
                    'datetime' => $datetime,
                    'verified' => $verified,
                    'status' => $status,
                    'workcode' => $workcode,
                ];
            }
        }

        return $fingerData;
    }

    private static function getValueFromTag($a, $b, $c)
    {
        $a = " ".$a;
        $hasil = "";
        $awal = strpos($a, $b);
        if ($awal != "") {
            $akhir = strpos(strstr($a, $b), $c);
            if ($akhir != "") {
                $hasil = substr($a, $awal+strlen($b), $akhir-strlen($b));
            }
        }
        return $hasil; 
    }

    private static function dateRange($startDate, $endDate)
    {
        $dateRange = [];
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            new \DateTime($endDate)
        );

        foreach ($period as $key => $value) {
            array_push($dateRange, $value->format('Y-m-d'));
        }

        array_push($dateRange, date('Y-m-d', strtotime($endDate)));

        return $dateRange;
    }

    private function generateRequest($payload)
    {
        $payload = str_replace("#COMKEY", static::$comkey, $payload);
        fputs(static::$conn, "POST /iWsService HTTP/1.0".self::$NL);
        fputs(static::$conn, "Content-Type: text/xml".self::$NL);
        fputs(static::$conn, "Content-Length: ".strlen($payload).self::$NL.self::$NL);
        fputs(static::$conn, $payload.self::$NL);
    }

    public function __call($method, $parameters)
    {
        return $this->$method(...$parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}