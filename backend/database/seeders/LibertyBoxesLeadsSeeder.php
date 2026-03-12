<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibertyBoxesLeadsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            $payload = [
                'source_name' => 'LibertyBoxes Spreadsheet',
                'ad_name' => $row['ad_name'] ?? 'LibertyBoxes',
                'platform' => $row['platform'] ?? null,
                'source_created_at' => $this->parseTimestamp($row['created_time'] ?? null),
                'lead_date_choice' => $row['lead_date_choice'] ?? null,
                'insurance_answer' => $row['insurance_answer'] ?? null,
                'full_name' => $row['full_name'] ?? null,
                'email' => $this->normalizeEmail($row['email'] ?? null),
                'phone' => $this->normalizePhone($row['phone'] ?? null),
                'city' => $row['city'] ?? null,
                'state' => $row['state'] ?? null,
                'carrier_class' => $row['carrier_class'] ?? null,
                'usdot' => $row['usdot'] ?? null,
                'truck_count' => $row['truck_count'] ?? null,
                'trailer_count' => $row['trailer_count'] ?? null,
                'lead_status' => 'new',
                'notes' => $row['notes'] ?? null,
                'raw_payload' => json_encode($row, JSON_UNESCAPED_UNICODE),
            ];

            $existingId = $this->findExistingLeadId($payload);

            if ($existingId) {
                DB::table('leads')
                    ->where('id', $existingId)
                    ->update(array_merge($payload, [
                        'updated_at' => now(),
                    ]));
            } else {
                DB::table('leads')->insert(array_merge($payload, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function findExistingLeadId(array $payload): ?int
    {
        if (!empty($payload['email'])) {
            $row = DB::table('leads')->select('id')->where('email', $payload['email'])->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        if (!empty($payload['phone'])) {
            $row = DB::table('leads')->select('id')->where('phone', $payload['phone'])->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        if (!empty($payload['full_name']) && !empty($payload['ad_name'])) {
            $row = DB::table('leads')
                ->select('id')
                ->where('full_name', $payload['full_name'])
                ->where('ad_name', $payload['ad_name'])
                ->first();

            if ($row) {
                return (int) $row->id;
            }
        }

        return null;
    }

    private function parseTimestamp(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->utc()->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeEmail(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim(strtolower($value));
        return $value !== '' ? $value : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/^p:/i', '', $value);
        $value = trim($value);

        $hasPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        return $hasPlus ? '+' . $digits : $digits;
    }

    private function rows(): array
    {
        return [
            [
                'created_time' => '2025-12-29T06:56:52-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'rushtran@gmail.com',
                'full_name' => 'Ronald Rush',
                'phone' => 'p:+17692899309',
                'city' => 'Meridian',
                'state' => null,
                'carrier_class' => 'Driver',
            ],
            [
                'created_time' => '2025-12-29T00:18:44-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'dm4montoya@gmail.com',
                'full_name' => 'David Montoya',
                'phone' => 'p:+16203909060',
                'city' => 'Garden City',
                'state' => null,
                'carrier_class' => 'OO-LeaseOn',
            ],
            [
                'created_time' => '2025-12-28T23:17:44-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => '3sixty5truckinginc@gmail.com',
                'full_name' => 'Taiwan Cameron',
                'phone' => 'p:+18183714814',
                'city' => 'North Hills',
                'state' => null,
                'carrier_class' => 'MicroCarrier (1-4)',
            ],
            [
                'created_time' => '2025-12-28T22:37:16-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'candelairan@yahoo.com',
                'full_name' => 'Iran Sanchez',
                'phone' => 'p:+13058076326',
                'city' => 'Del Rio',
                'state' => null,
                'carrier_class' => 'SmallCarrier (5-10)',
            ],
            [
                'created_time' => '2025-12-28T22:00:41-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'truckingeee@gmail.com',
                'full_name' => 'Erland Rayos',
                'phone' => 'p:+19707494205',
                'city' => 'Durango',
                'state' => null,
                'carrier_class' => 'Carrier (10+)',
            ],
            [
                'created_time' => '2025-12-28T21:56:28-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'yes',
                'email' => 'galvand2012@gmail.com',
                'full_name' => 'Daniel Galvan',
                'phone' => 'p:+19405508938',
                'city' => 'Graham',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T21:06:43-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'btlad77@yahoo.com',
                'full_name' => 'Bolaji',
                'phone' => 'p:+18324996846',
                'city' => 'Richmond',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T20:45:03-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'yes',
                'email' => 'BigBearLND25@gmail.com',
                'full_name' => 'Daniel Coy',
                'phone' => 'p:+15126290678',
                'city' => 'Buda',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T20:22:47-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'tris8910@aol.com',
                'full_name' => 'Tristan Dorsey',
                'phone' => 'p:+18438334984',
                'city' => 'Vestal',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T19:35:46-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'Jvesc08@icloud.com',
                'full_name' => 'Juan Bautista',
                'phone' => 'p:+12544331282',
                'city' => 'Eastland',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T19:21:06-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'Westcoastallstarz@yahoo.com',
                'full_name' => 'Dwayne Fastlane Thompson',
                'phone' => 'p:+19099916987',
                'city' => 'Los Angeles',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T19:20:03-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'yes',
                'email' => 'Edward.d.leal@gmail.com',
                'full_name' => 'Edward Leal',
                'phone' => 'p:3618330189',
                'city' => 'Freer',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T19:04:31-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'gatkuoth1985@gmail.com',
                'full_name' => 'Joseph Gatkuoth',
                'phone' => 'p:+12063766914',
                'city' => 'Crowley',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T18:02:59-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'yes',
                'email' => 'georobinssr@aol.com',
                'full_name' => 'George Robinson',
                'phone' => 'p:+13302330190',
                'city' => 'Youngstown',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T16:54:45-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'yes',
                'email' => 'genro4casteneda@gmail.com',
                'full_name' => 'Genaro Castaneda',
                'phone' => 'p:+19795740594',
                'city' => 'Caldwell',
                'state' => null,
            ],
            [
                'created_time' => '2025-12-28T14:36:04-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'yes',
                'email' => 'jmelitehotshot@yahoo.com',
                'full_name' => 'JoJo Peralta',
                'phone' => 'p:+18303177971',
                'city' => 'Pearsall',
                'state' => null,
            ],
            [
                'created_time' => null,
                'ad_name' => 'LibertyBoxes',
                'platform' => null,
                'lead_date_choice' => null,
                'insurance_answer' => null,
                'email' => 'Vroberts25@icloud.com',
                'full_name' => 'Vernon Ropberts',
                'phone' => '9257556467',
                'city' => 'Houston',
                'state' => null,
                'notes' => 'Row had missing source metadata in spreadsheet.',
            ],
            [
                'created_time' => null,
                'ad_name' => 'LibertyBoxes',
                'platform' => null,
                'lead_date_choice' => null,
                'insurance_answer' => null,
                'email' => null,
                'full_name' => 'Carlos Figeura',
                'phone' => '14023321218',
                'city' => 'San Antonio',
                'state' => null,
                'notes' => 'Row had missing source metadata in spreadsheet.',
            ],
            [
                'created_time' => null,
                'ad_name' => 'LibertyBoxes',
                'platform' => null,
                'lead_date_choice' => null,
                'insurance_answer' => null,
                'email' => null,
                'full_name' => 'Joe Carmona',
                'phone' => null,
                'city' => 'San Anotio',
                'state' => null,
                'notes' => 'Row had missing source metadata in spreadsheet.',
            ],
            [
                'created_time' => '2025-12-29T06:22:17-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'flintstepper@icloud.com',
                'full_name' => 'Lawrence Wendland',
                'phone' => 'p:+15125903090',
                'city' => 'Springtown',
                'state' => 'TX',
                'notes' => 'Spreadsheet had extra numeric value: 1',
            ],
            [
                'created_time' => '2025-12-29T06:22:11-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'troyjones371@gmail.com',
                'full_name' => 'Troy Jones',
                'phone' => 'p:+13187192670',
                'city' => 'Baton Rouge',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 2',
            ],
            [
                'created_time' => '2025-12-29T04:40:59-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'N8manuel@gmail.com',
                'full_name' => 'Nate Coleman',
                'phone' => 'p:+18324960510',
                'city' => 'Houston',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 3',
            ],
            [
                'created_time' => '2025-12-29T03:56:55-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'carterjr.marvinr@yahoo.com',
                'full_name' => 'Marvin R Carter Jr.',
                'phone' => 'p:+16824721324',
                'city' => 'Arlington',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 4',
            ],
            [
                'created_time' => '2025-12-29T00:20:38-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '5-10_days',
                'insurance_answer' => 'no',
                'email' => 'rwfrisch702@gmail.com',
                'full_name' => 'Robert Frisch',
                'phone' => 'p:+13073154827',
                'city' => 'Custer',
                'state' => 'SD',
                'notes' => 'Spreadsheet had extra numeric value: 5',
            ],
            [
                'created_time' => '2025-12-28T23:51:42-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => '5-10_days',
                'insurance_answer' => 'no',
                'email' => 'mmu3021964@gmail.com',
                'full_name' => 'Allen Keith McKean',
                'phone' => 'p:+15054019404',
                'city' => 'Hearne',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 6',
            ],
            [
                'created_time' => '2025-12-28T23:28:48-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'lrios1025@gmail.com',
                'full_name' => 'Librado Rios Jr.',
                'phone' => 'p:+19569744491',
                'city' => 'Edinburg',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 7',
            ],
            [
                'created_time' => '2025-12-28T22:05:19-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '5-10_days',
                'insurance_answer' => 'no',
                'email' => 'kdmsr69@gmail.com',
                'full_name' => 'Pwee McCarter',
                'phone' => 'p:+14322029691',
                'city' => 'Houston',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 8',
            ],
            [
                'created_time' => '2025-12-28T22:03:43-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'terrencewest99@yahoo.com',
                'full_name' => 'Terrence West',
                'phone' => 'p:+146983337166',
                'city' => 'Florissant',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 9',
            ],
            [
                'created_time' => '2025-12-28T21:53:32-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '5-10_days',
                'insurance_answer' => 'no',
                'email' => 'ruiztransportation@yahoo.com',
                'full_name' => 'Pete Ruiz',
                'phone' => 'p:+19565863670',
                'city' => 'San Juan',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 10',
            ],
            [
                'created_time' => '2025-12-28T20:57:25-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'kendrick.butcher@icloud.com',
                'full_name' => 'Kendrick Butcher',
                'phone' => 'p:+19363233242',
                'city' => 'Huntsville',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 11',
            ],
            [
                'created_time' => '2025-12-28T20:25:28-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'patrickhwright65@gmail.com',
                'full_name' => 'Patrick Wright',
                'phone' => 'p:+18595332155',
                'city' => 'Lexington',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 12',
            ],
            [
                'created_time' => '2025-12-28T19:53:34-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'Oblogisticsllc25@gmail.com',
                'full_name' => 'Brian Manuel Gonzalez',
                'phone' => 'p:+13257257711',
                'city' => 'Laredo',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 13',
            ],
            [
                'created_time' => '2025-12-28T19:08:14-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'williamgreen01@bellsouth.net',
                'full_name' => 'William Lester Green',
                'phone' => 'p:+16623039188',
                'city' => 'Indianola',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 14',
            ],
            [
                'created_time' => '2025-12-28T18:58:29-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'scorpionenterprisesllc@outlook.com',
                'full_name' => 'Benny Moore',
                'phone' => 'p:+17753740227',
                'city' => 'Eureka',
                'state' => 'Nevada',
                'notes' => 'Spreadsheet had extra numeric value: 15',
            ],
            [
                'created_time' => '2025-12-28T18:35:39-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'paulcad2003@yahoo.com',
                'full_name' => 'Paul Escalona',
                'phone' => 'p:+13616521375',
                'city' => 'La Victoria',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 16',
            ],
            [
                'created_time' => '2025-12-28T18:32:17-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'oasamoah007@gmail.com',
                'full_name' => 'OB1',
                'phone' => 'p:+15802915669',
                'city' => 'Grand Prairie',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 17',
            ],
            [
                'created_time' => '2025-12-28T17:59:29-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'charlot_bryan@yahoo.com',
                'full_name' => 'Bryan Charlot',
                'phone' => 'p:2254283218',
                'city' => 'Opelousas',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 18',
            ],
            [
                'created_time' => '2025-12-28T17:54:19-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'ig',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'wedreambigtrucking@gmail.com',
                'full_name' => 'Im Not Ur Type',
                'phone' => 'p:+3466282853',
                'city' => 'Katy',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 19',
            ],
            [
                'created_time' => '2025-12-28T16:51:47-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'Jdbsolutionz@gmail.com',
                'full_name' => 'Jay Brown',
                'phone' => 'p:+19855160875',
                'city' => 'Hammond',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 20',
            ],
            [
                'created_time' => '2025-12-28T16:38:02-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'arnoram123@gmail.com',
                'full_name' => 'Arnold Ramirez',
                'phone' => 'p:+13612310463',
                'city' => 'Hebbronville',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 21',
            ],
            [
                'created_time' => '2025-12-28T14:54:23-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'ahmad.cummings@yahoo.com',
                'full_name' => 'Ahmad Cummings',
                'phone' => 'p:+18323261533',
                'city' => 'Waco',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 22',
            ],
            [
                'created_time' => '2025-12-28T13:49:52-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'Danny.777gonzalez9876@gmail.com',
                'full_name' => 'Daniel Gonzalez',
                'phone' => 'p:+18324728115',
                'city' => 'North Houston',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 23',
            ],
            [
                'created_time' => '2025-12-28T13:43:00-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => '1-5_days',
                'insurance_answer' => 'no',
                'email' => 'moraisarielso@yahoo.com',
                'full_name' => 'Arielso Morais',
                'phone' => 'p:+14695312889',
                'city' => 'Corinth',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 24',
            ],
            [
                'created_time' => '2025-12-28T13:40:59-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'Marquellmartin33@gmail.com',
                'full_name' => 'Marquell Martin',
                'phone' => 'p:+14059673823',
                'city' => 'Fort Worth',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 25',
            ],
            [
                'created_time' => '2025-12-28T13:39:32-07:00',
                'ad_name' => 'LibertyBoxes',
                'platform' => 'fb',
                'lead_date_choice' => 'in_january',
                'insurance_answer' => 'no',
                'email' => 'traeyruss@gmail.com',
                'full_name' => 'DiTraeyvious Russell',
                'phone' => 'p:+16627191184',
                'city' => 'Sunflower',
                'state' => null,
                'notes' => 'Spreadsheet had extra numeric value: 26',
            ],
        ];
    }
}
