<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class NetatmoAircareSensor extends IPSModule
{
    use NetatmoAircare\StubsCommonLib;
    use NetatmoAircareLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonConstruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('product_type', '');
        $this->RegisterPropertyString('product_id', '');

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_last_measure', false);
        $this->RegisterPropertyBoolean('with_wifi_strength', false);
        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_absolute_humidity', false);
        $this->RegisterPropertyBoolean('with_dewpoint', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_minmax', false);
        $this->RegisterPropertyInteger('minutes2fail', 30);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{070C93FD-9D19-D670-2C73-20104B87F034}');
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $product_type = $this->ReadPropertyString('product_type');
        if ($product_type == '') {
            $this->SendDebug(__FUNCTION__, '"product_type" is empty', 0);
            $r[] = $this->Translate('Product-Type must be specified');
        }

        $product_id = $this->ReadPropertyString('product_id');
        if ($product_id == '') {
            $this->SendDebug(__FUNCTION__, '"product_id" is empty', 0);
            $r[] = $this->Translate('Product-ID must be specified');
        }

        return $r;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_wifi_strength = $this->ReadPropertyBoolean('with_wifi_strength');
        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');

        $vpos = 1;

        $this->MaintainVariable('Status', $this->Translate('State'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('LastContact', $this->Translate('last transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
        $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
        $this->MaintainVariable('WifiStrength', $this->Translate('Strength of wifi-signal'), VARIABLETYPE_INTEGER, 'NetatmoAircare.WifiStrength', $vpos++, $with_wifi_strength);

        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, true);
        $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Humidity', $vpos++, true);
        $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.absHumidity', $vpos++, $with_absolute_humidity);
        $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Dewpoint', $vpos++, $with_dewpoint);
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Heatindex', $vpos++, $with_heatindex);
        $this->MaintainVariable('CO2', $this->Translate('CO2'), VARIABLETYPE_INTEGER, 'NetatmoAircare.CO2', $vpos++, true);
        $this->MaintainVariable('Noise', $this->Translate('Noise'), VARIABLETYPE_INTEGER, 'NetatmoAircare.Noise', $vpos++, true);
        $this->MaintainVariable('Pressure', $this->Translate('Air pressure'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Pressure', $vpos++, true);
        $this->MaintainVariable('AbsolutePressure', $this->Translate('absolute pressure'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Pressure', $vpos++, $with_absolute_pressure);
        $this->MaintainVariable('Index', $this->Translate('Air Quality Health Index'), VARIABLETYPE_INTEGER, 'NetatmoAircare.Index', $vpos++, true);

        $product_id = $this->ReadPropertyString('product_id');
        $product_type = $this->ReadPropertyString('product_type');
        $product_info = $product_id . ' (' . $product_type . ')';
        $this->SetSummary($product_info);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function GetFormElements()
    {
        $product_type = $this->ReadPropertyString('product_type');
        switch ($product_type) {
            case 'NHC':
                $product_type_s = 'Netatmo Room air sensor';
                break;
            default:
                $product_type_s = 'Netatmo Aircare';
                break;
        }

        $formElements = $this->GetCommonFormElements($product_type_s);

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'enabled' => false,
                    'name'    => 'product_type',
                    'caption' => 'Product-Type'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'enabled' => false,
                    'name'    => 'product_id',
                    'caption' => 'Product-ID'
                ],
            ],
            'caption' => 'Basic configuration (don\'t change)',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_last_contact',
                    'caption' => 'last transmission to Netatmo'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_last_measure',
                    'caption' => 'Measurement-Timestamp'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_wifi_strength',
                    'caption' => 'Strength of wifi-signal'
                ],
            ],
            'caption' => 'optional data',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_absolute_pressure',
                    'caption' => 'absolute pressure'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_absolute_humidity',
                    'caption' => 'absolute humidity'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_dewpoint',
                    'caption' => 'Dewpoint'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_heatindex',
                    'caption' => 'Heatindex'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'with_minmax',
                    'caption' => 'Min/Max of temperature'
                ],
            ],
            'caption' => 'optional weather data'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'Duration until the connection to netatmo or between stations is marked disturbed'
                ],
                [
                    'type'     => 'NumberSpinner',
                    'minimum'  => 0,
                    'suffix'   => 'Minutes',
                    'name'     => 'minutes2fail',
                ],
            ],
            'caption' => 'Processing information'
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded'  => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function ReceiveData($data)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $now = time();

        $product_id = $this->ReadPropertyString('product_id');

        if ($data != '') {
            $jdata = json_decode($data, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

            $buf = $this->GetArrayElem($jdata, 'Buffer', '');
            $this->SendDebug(__FUNCTION__, 'buf=' . print_r($buf, true), 0);
            $jbuf = json_decode($buf, true);
            $this->SendDebug(__FUNCTION__, 'jbuf=' . print_r($jbuf, true), 0);

            $body = $this->GetArrayElem($jbuf, 'body', '');
            $devices = $this->GetArrayElem($jbuf, 'body.devices', '');
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if ($devices != '') {
                foreach ($devices as $device) {
                    if ($product_id != $device['_id']) {
                        continue;
                    }
                    $this->SendDebug(__FUNCTION__, 'decode device=' . print_r($device, true), 0);

                    $minutes2fail = $this->ReadPropertyInteger('minutes2fail');
                    $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
                    $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
                    $with_wifi_strength = $this->ReadPropertyBoolean('with_wifi_strength');
                    $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
                    $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
                    $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
                    $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
                    $with_minmax = $this->ReadPropertyBoolean('with_minmax');

                    $station_status = true;
                    $last_contact = $device['last_status_store'];
                    if (is_int($last_contact)) {
                        $sec = $now - $last_contact;
                        $min = floor($sec / 60);
                        if ($min > $minutes2fail) {
                            $station_status = false;
                        }
                    } else {
                        $last_contact = 0;
                    }

                    $this->SetValue('Status', $station_status);

                    if ($with_last_contact) {
                        $this->SetValue('LastContact', $last_contact);
                    }

                    if ($with_wifi_strength) {
                        $wifi_status = $this->map_wifi_strength($device['wifi_status']);
                        $this->SetValue('WifiStrength', $wifi_status);
                    }
                    if ($with_last_measure) {
                        $last_measure = $this->GetArrayElem($device, 'dashboard_data.time_utc', 0);
                        $this->SetValue('LastMeasure', $last_measure);
                    }

                    $Temperature = $this->GetArrayElem($device, 'dashboard_data.Temperature', 0);
                    $CO2 = $this->GetArrayElem($device, 'dashboard_data.CO2', 0);
                    $Humidity = $this->GetArrayElem($device, 'dashboard_data.Humidity', 0);
                    $Noise = $this->GetArrayElem($device, 'dashboard_data.Noise', 0);
                    $Pressure = $this->GetArrayElem($device, 'dashboard_data.Pressure', 0);

                    $AbsolutePressure = $this->GetArrayElem($device, 'dashboard_data.AbsolutePressure', 0);
                    $health_idx = $this->GetArrayElem($device, 'dashboard_data.health_idx', 0);
                    $min_temp = $this->GetArrayElem($device, 'dashboard_data.min_temp', 0);
                    $max_temp = $this->GetArrayElem($device, 'dashboard_data.max_temp', 0);
                    $date_max_temp = $this->GetArrayElem($device, 'dashboard_data.date_max_temp', 0);
                    $date_min_temp = $this->GetArrayElem($device, 'dashboard_data.date_min_temp', 0);

                    $this->SetValue('Temperature', $Temperature);
                    if ($with_minmax) {
                        $this->SetValue('TemperatureMax', $max_temp);
                        $this->SetValue('TemperatureMaxTimestamp', $date_max_temp);
                        $this->SetValue('TemperatureMin', $min_temp);
                        $this->SetValue('TemperatureMinTimestamp', $date_min_temp);
                    }
                    $this->SetValue('Humidity', $Humidity);
                    if ($with_absolute_humidity) {
                        $abs_humidity = $this->CalcAbsoluteHumidity($Temperature, $Humidity);
                        $this->SetValue('AbsoluteHumidity', $abs_humidity);
                    }
                    if ($with_dewpoint) {
                        $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
                        $this->SetValue('Dewpoint', $dewpoint);
                    }
                    if ($with_heatindex) {
                        $heatindex = $this->CalcHeatindex($Temperature, $Humidity);
                        $this->SetValue('Heatindex', $heatindex);
                    }
                    $this->SetValue('CO2', $CO2);
                    $this->SetValue('Noise', $Noise);
                    $this->SetValue('Pressure', $Pressure);
                    if ($with_absolute_pressure) {
                        $this->SetValue('AbsolutePressure', $AbsolutePressure);
                    }
                    $this->SetValue('Index', $health_idx);
                }
            }
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    // Wifi-Strength
    private function map_wifi_strength($strength)
    {
        if ($strength <= 56) {
            $val = self::$WIFI_HIGH;
        } elseif ($strength <= 71) {
            $val = self::$WIFI_MEDIUM;
        } else {
            $val = self::$WIFI_LOW;
        }

        return $val;
    }

    private function wifi_strength2text($strength)
    {
        return $this->CheckVarProfile4Value('NetatmoAircare.WifiStrength', $status);
    }

    private function wifi_strength2icon($strength)
    {
        $strength2icon = [
            'wifi_low.png',
            'wifi_medium.png',
            'wifi_high.png',
        ];

        if ($strength >= 0 && $strength < count($strength2icon)) {
            $img = $strength2icon[$strength];
        } else {
            $img = '';
        }
        return $img;
    }

    public function WifiStrength2Icon(string $wifi_strength, bool $asPath)
    {
        $img = $this->wifi_strength2icon($wifi_strength);
        /*
        if ($img != false) {
            $hook = $this->ReadPropertyString('hook');
            $img = $hook . '/imgs/' . $img;
        }
         */
        return $img;
    }

    public function WifiStrength2Text(string $wifi_strength)
    {
        $txt = $this->wifi_strength2text($wifi_strength);
    }

    // Taupunkt berechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    public function CalcDewpoint(float $temp, float $humidity)
    {
        if ($temp > 0) {
            $k2 = 17.62;
            $k3 = 243.12;
        } else {
            $k2 = 22.46;
            $k3 = 272.62;
        }
        $dewpoint = $k3 * (($k2 * $temp) / ($k3 + $temp) + log($humidity / 100));
        $dewpoint = $dewpoint / (($k2 * $k3) / ($k3 + $temp) - log($humidity / 100));
        $dewpoint = round($dewpoint, 0);
        return $dewpoint;
    }

    // relative Luffeuchtigkeit in absolute Feuchte umrechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    public function CalcAbsoluteHumidity(float $temp, float $humidity)
    {
        if ($temp >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }

        $R = 8314.3; // universelle Gaskonstante in J/(kmol*K)
        $mw = 18.016; // Molekulargewicht des Wasserdampfes in kg/kmol

        // Sättigungsdamphdruck in hPa
        $SDD = 6.1078 * pow(10, (($a * $temp) / ($b + $temp)));

        // Dampfdruck in hPa
        $DD = $humidity / 100 * $SDD;

        $v = log10($DD / 6.1078);

        // Taupunkttemperatur in °C
        $TD = $b * $v / ($a - $v);

        // Temperatur in Kelvin
        $TK = $temp + 273.15;

        // absolute Feuchte in g Wasserdampf pro m³ Luft
        $AF = pow(10, 5) * $mw / $R * $DD / $TK;
        $AF = round($AF * 10) / 10; // auf eine NK runden

        return $AF;
    }

    // Temperatur als Heatindex umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Hitzeindex
    public function CalcHeatindex(float $temp, float $hum)
    {
        if ($temp < 27 || $hum < 40) {
            return $temp;
        }
        $c1 = -8.784695;
        $c2 = 1.61139411;
        $c3 = 2.338549;
        $c4 = -0.14611605;
        $c5 = -1.2308094 * pow(10, -2);
        $c6 = -1.6424828 * pow(10, -2);
        $c7 = 2.211732 * pow(10, -3);
        $c8 = 7.2546 * pow(10, -4);
        $c9 = -3.582 * pow(10, -6);

        $hi = $c1
            + $c2 * $temp
            + $c3 * $hum
            + $c4 * $temp * $hum
            + $c5 * pow($temp, 2)
            + $c6 * pow($hum, 2)
            + $c7 * pow($temp, 2) * $hum
            + $c8 * $temp * pow($hum, 2)
            + $c9 * pow($temp, 2) * pow($hum, 2);
        $hi = round($hi); // ohne NK
        return $hi;
    }
}
