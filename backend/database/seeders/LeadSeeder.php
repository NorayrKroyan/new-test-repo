<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $records = array_merge(
            $this->websiteCarrierLeads(),
            $this->libertyBoxesLeads(),
            $this->stxHoppersLeads(),
        );

        // If you want to clear the table before seeding, uncomment these lines:
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('leads')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach (array_chunk($records, 200) as $chunk) {
            DB::table('leads')->insert($chunk);
        }
    }

    private function websiteCarrierLeads(): array
    {
        $rows = [
            [
                'submission_date' => '2025-08-02 18:37:11',
                'first_name' => 'Jason',
                'last_name' => 'Gentry',
                'phone' => '(940) 389-4453',
                'email' => 'gentryj2000@gmail.com',
                'role' => 'CDL Class A Driver Looking for Work',
                'city' => 'Paradise',
                'state' => 'Texas',
                'trailers' => "Ag Hopper Bottom Trailers (22\" Clearance)\nBelly Dump",
                'haul_area' => 'Central Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)',
                'submission_ip' => '132.147.145.248',
                'usdot' => null,
                'submission_id' => '6299906318423440998',
            ],
            [
                'submission_date' => '2025-08-04 17:15:17',
                'first_name' => 'Demario',
                'last_name' => 'White',
                'phone' => '(210) 845-7441',
                'email' => 'countyrli@gmail.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'San Antonio',
                'state' => 'Texas',
                'trailers' => "Power Only, use trailers provided by the customer\nAg Hopper Bottom Trailers (22\" Clearance)\nFrac Sand Chassis (Single or Double Box)\nStep Decks\nBelly Dump",
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '2603:8081:5e00:356:b45e:8c4:1499:e65',
                'usdot' => '4354243',
                'submission_id' => '6301585175699559233',
            ],
            [
                'submission_date' => '2025-12-06 9:53:21',
                'first_name' => 'Dave',
                'last_name' => 'Taylor',
                'phone' => '(404) 379-7317',
                'email' => 'dave.dbusinessman@gmail.com',
                'role' => 'I have my own authority',
                'city' => 'Houston',
                'state' => 'Texas',
                'trailers' => 'Ag Hopper Bottom Trailers (22" Clearance)',
                'haul_area' => "Central Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)\nSouth Central (Texas, Louisiana, New Mexico)",
                'submission_ip' => '73.115.79.167',
                'usdot' => '3183517',
                'submission_id' => '6408492017616419832',
            ],
            [
                'submission_date' => '2025-12-30 16:21:56',
                'first_name' => 'Luis',
                'last_name' => 'Calderon',
                'phone' => '(325) 338-0005',
                'email' => 'luis@7transportllc.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'Kermit',
                'state' => 'Texas',
                'trailers' => "Power Only, use trailers provided by the customer\nAg Hopper Bottom Trailers (22\" Clearance)",
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '174.226.191.55',
                'usdot' => null,
                'submission_id' => '6429461165519318012',
            ],
            [
                'submission_date' => '2026-01-11 21:40:12',
                'first_name' => 'Olawale',
                'last_name' => 'Abayomi',
                'phone' => '(469) 648-9646',
                'email' => 'walewgl@yahoo.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'DAllas',
                'state' => 'Texas',
                'trailers' => "Power Only, use trailers provided by the customer\nFrac Sand Chassis (Single or Double Box)",
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '23.123.45.206',
                'usdot' => null,
                'submission_id' => '6440020126024027202',
            ],
            [
                'submission_date' => '2026-01-14 21:49:46',
                'first_name' => 'Matt',
                'last_name' => 'Miller',
                'phone' => '(580) 304-4565',
                'email' => 'mattm1985@hotmail.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'El reno',
                'state' => 'Oklahoma',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => "Central Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)\nSouth Central (Texas, Louisiana, New Mexico)",
                'submission_ip' => '104.56.22.24',
                'usdot' => '4390469',
                'submission_id' => '6442617864223623839',
            ],
            [
                'submission_date' => '2026-01-15 18:22:33',
                'first_name' => 'Marc',
                'last_name' => 'Johnson',
                'phone' => '(903) 504-4129',
                'email' => 'dmarcusdjohnson@gmail.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'Houston',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '50.172.134.114',
                'usdot' => null,
                'submission_id' => '6443357534116863703',
            ],
            [
                'submission_date' => '2026-01-17 14:27:02',
                'first_name' => 'Renato',
                'last_name' => 'Garza Jr.',
                'phone' => '(956) 358-7910',
                'email' => 'renatogarza65@gmail.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'penitas',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '172.226.175.21',
                'usdot' => '1413939',
                'submission_id' => '6444944221252147888',
            ],
            [
                'submission_date' => '2026-01-17 14:32:14',
                'first_name' => 'Renato',
                'last_name' => 'Garza Jr.',
                'phone' => '(956) 458-7910',
                'email' => 'ratransportationllc@icloud.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'penita',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '172.226.175.23',
                'usdot' => '1413939',
                'submission_id' => '6444947343254314346',
            ],
            [
                'submission_date' => '2026-01-17 16:15:29',
                'first_name' => 'Renato',
                'last_name' => 'Garza Jr.',
                'phone' => '(956) 358-7910',
                'email' => 'renatogarza65@gmail.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'penitas',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '172.225.204.73',
                'usdot' => '1413939',
                'submission_id' => '6445009293743708557',
            ],
            [
                'submission_date' => '2026-01-17 19:54:27',
                'first_name' => 'William',
                'last_name' => 'Renfrow',
                'phone' => '(539) 266-1388',
                'email' => 'renfrowtrucking@yahoo.com',
                'role' => 'Owner operator my own authority a few trucks no trailers',
                'city' => 'Sallisaw',
                'state' => 'Oklahoma',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '174.202.162.189',
                'usdot' => '2148890',
                'submission_id' => '6445140679817407096',
            ],
            [
                'submission_date' => '2026-01-20 6:17:51',
                'first_name' => 'Marc',
                'last_name' => 'Johnson',
                'phone' => '(903) 504-4129',
                'email' => 'DmarcusDJohnson@gmail.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'Houston',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '50.172.134.114',
                'usdot' => null,
                'submission_id' => '6447242714115510881',
            ],
            [
                'submission_date' => '2026-01-20 10:43:48',
                'first_name' => 'Olawale',
                'last_name' => 'Abayomi',
                'phone' => '(469) 648-9646',
                'email' => 'wesleygllc@yahoo.com',
                'role' => 'CDL Class A Driver Looking for Work',
                'city' => 'Dallas',
                'state' => 'Texas',
                'trailers' => "Power Only, use trailers provided by the customer\nStep Decks",
                'haul_area' => "Central Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)\nSouth Central (Texas, Louisiana, New Mexico)",
                'submission_ip' => '23.123.45.206',
                'usdot' => '4421035',
                'submission_id' => '6447402286025640008',
            ],
            [
                'submission_date' => '2026-01-26 17:31:29',
                'first_name' => 'Dustin',
                'last_name' => 'Lewis',
                'phone' => '(402) 304-7898',
                'email' => 'crystalseallc@yahoo.com',
                'role' => 'CDL Class A Driver Looking for Work',
                'city' => 'Omaha',
                'state' => 'Nebraska',
                'trailers' => "Power Only, use trailers provided by the customer\nAg Hopper Bottom Trailers (22\" Clearance)\nDry Van\nReefer",
                'haul_area' => "Upper Midwest (North Dakota, South Dakota, Montana, Minnesota, Wyoming)\nCentral Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)",
                'submission_ip' => '174.215.242.232',
                'usdot' => "Don't have one yet but hope to get one soon",
                'submission_id' => '6452830892326647397',
            ],
            [
                'submission_date' => '2026-01-26 18:34:56',
                'first_name' => 'Karl',
                'last_name' => 'Dietel',
                'phone' => '(575) 636-3669',
                'email' => 'northvalleyleasing@hotmail.com',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'Las Cruces',
                'state' => 'New Mexico',
                'trailers' => "Power Only, use trailers provided by the customer\nDry Van",
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '98.48.60.81',
                'usdot' => '1535484',
                'submission_id' => '6452868961803867657',
            ],
            [
                'submission_date' => '2026-01-27 2:03:02',
                'first_name' => 'Jarvis',
                'last_name' => 'Weaver',
                'phone' => '(940) 447-6810',
                'email' => 'elranchodedew@gmail.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'Archer City',
                'state' => 'Texas',
                'trailers' => 'Power Only, use trailers provided by the customer',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '72.158.228.117',
                'usdot' => '1296929',
                'submission_id' => '6453137827116312033',
            ],
            [
                'submission_date' => '2026-01-29 18:12:21',
                'first_name' => 'Donald',
                'last_name' => 'Carlisle',
                'phone' => '(903) 930-8694',
                'email' => 'doncar747@yahoo.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'Humble',
                'state' => 'Texas',
                'trailers' => 'Flatbeds',
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '172.56.25.185',
                'usdot' => '2952906',
                'submission_id' => '6455447415812317384',
            ],
            [
                'submission_date' => '2026-02-04 11:10:19',
                'first_name' => 'Scott',
                'last_name' => 'Noud',
                'phone' => '(307) 630-2917',
                'email' => 'lancer1429@yahoo.com',
                'role' => 'Owner operator with own authority',
                'city' => 'Cheyenne',
                'state' => 'Wyoming',
                'trailers' => "Power Only, use trailers provided by the customer\nAg Hopper Bottom Trailers (22\" Clearance)\nSide Dump\nBelly Dump",
                'haul_area' => "Upper Midwest (North Dakota, South Dakota, Montana, Minnesota, Wyoming)\nCentral Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)",
                'submission_ip' => '146.75.146.0',
                'usdot' => '1925798',
                'submission_id' => '6460378190647676023',
            ],
            [
                'submission_date' => '2026-02-26 16:46:06',
                'first_name' => 'Cesar',
                'last_name' => 'Guerrero',
                'phone' => '(195) 635-2919',
                'email' => 'cctranslinellc1@yahoo.con',
                'role' => 'Small Fleet (1-4 Trucks on our MC)',
                'city' => 'Edinburg',
                'state' => 'Texas',
                'trailers' => "Power Only, use trailers provided by the customer\nFrac Sand Chassis (Single or Double Box)",
                'haul_area' => 'South Central (Texas, Louisiana, New Mexico)',
                'submission_ip' => '107.127.0.55',
                'usdot' => '3420170',
                'submission_id' => '6479587665509963115',
            ],
            [
                'submission_date' => '2026-03-12 12:44:36',
                'first_name' => 'Ronaldo',
                'last_name' => 'Martell',
                'phone' => '(720) 534-1492',
                'email' => 'cec.rmartell@gmail.com',
                'role' => 'Owner Operator (under someone elses authority)',
                'city' => 'Benett',
                'state' => 'Colorado',
                'trailers' => "Side Dump\nBelly Dump",
                'haul_area' => 'Central Midwest (Kansas, Colorado, Missouri, Oklahoma, Utah)',
                'submission_ip' => '174.201.3.94',
                'usdot' => '3915105',
                'submission_id' => '6491502764932869368',
            ],
        ];

        $records = [];

        foreach ($rows as $row) {
            $records[] = $this->buildLead([
                'source_name' => 'website_carrier_form',
                'ad_name' => 'Website Carrier Leads',
                'platform' => 'website',
                'source_created_at' => $row['submission_date'],
                'lead_date_choice' => null,
                'insurance_answer' => null,
                'full_name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'email' => $row['email'],
                'phone' => $row['phone'],
                'city' => $row['city'],
                'state' => $row['state'],
                'carrier_class' => $row['role'],
                'usdot' => $row['usdot'],
                'notes' => $this->noteLines([
                    'Trailers: ' . $row['trailers'],
                    'Interested area: ' . $row['haul_area'],
                    'Submission IP: ' . $row['submission_ip'],
                    'Submission ID: ' . $row['submission_id'],
                ]),
                'raw_payload' => $row,
            ]);
        }

        return $records;
    }

    private function libertyBoxesLeads(): array
    {
        $common = [
            'ad_id' => 'ag:120236731893640766',
            'ad_name' => 'LibertyBoxes',
            'adset_id' => 'as:120236731893660766',
            'adset_name' => 'STX-WYO',
            'campaign_id' => 'c:120236731893650766',
            'campaign_name' => 'STX-Lib',
            'form_id' => 'f:1246374967343129',
            'form_name' => 'STXBoxForm',
        ];

        $rows = [
            ['lead_id' => 'l:832282019707917', 'created_time' => '2025-12-29T06:56:52-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'rushtran@gmail.com', 'full_name' => 'Ronald Rush', 'phone_number' => 'p:+17692899309', 'city' => 'Meridian', 'inbox_url' => 'https://business.facebook.com/latest/25577790138577897?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2363086580778746', 'created_time' => '2025-12-29T06:22:17-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'flintstepper@icloud.com', 'full_name' => 'Lawrence Wendland', 'phone_number' => 'p:+15125903090', 'city' => 'Springtown, Tx', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2171556730425596', 'created_time' => '2025-12-29T06:22:11-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'troyjones371@gmail.com', 'full_name' => 'Troy Jones', 'phone_number' => 'p:+13187192670', 'city' => 'Baton Rouge', 'inbox_url' => 'https://business.facebook.com/latest/25473121375686416?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1204810091576773', 'created_time' => '2025-12-29T04:40:59-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'N8manuel@gmail.com', 'full_name' => 'Nate Coleman', 'phone_number' => 'p:+18324960510', 'city' => 'Houston', 'inbox_url' => 'https://business.facebook.com/latest/32944439855199345?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:898928932659638', 'created_time' => '2025-12-29T03:56:55-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'carterjr.marvinr@yahoo.com', 'full_name' => 'Marvin R Carter Jr.', 'phone_number' => 'p:+16824721324', 'city' => 'Arlington', 'inbox_url' => 'https://business.facebook.com/latest/24937752209239997?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2873938669458842', 'created_time' => '2025-12-29T00:20:38-07:00', 'platform' => 'fb', 'availability' => '5-10_days', 'insurance' => 'no', 'email' => 'rwfrisch702@gmail.com', 'full_name' => 'Robert Frisch', 'phone_number' => 'p:+13073154827', 'city' => 'Custer, SD', 'inbox_url' => 'https://business.facebook.com/latest/32846713401643159?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2796895730653103', 'created_time' => '2025-12-29T00:18:44-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'dm4montoya@gmail.com', 'full_name' => 'David Montoya', 'phone_number' => 'p:+16203909060', 'city' => 'Garden City', 'inbox_url' => 'https://business.facebook.com/latest/9454336908023816?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2038726053559595', 'created_time' => '2025-12-28T23:51:42-07:00', 'platform' => 'ig', 'availability' => '5-10_days', 'insurance' => 'no', 'email' => 'mmu3021964@gmail.com', 'full_name' => 'Allen Keith McKean', 'phone_number' => 'p:+15054019404', 'city' => 'Hearne', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1284205860202823', 'created_time' => '2025-12-28T23:28:48-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'lrios1025@gmail.com', 'full_name' => 'Librado Rios Jr.', 'phone_number' => 'p:+19569744491', 'city' => 'edinburg', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1538054404138912', 'created_time' => '2025-12-28T23:17:44-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => '3sixty5truckinginc@gmail.com', 'full_name' => 'Taiwan Cameron', 'phone_number' => 'p:+18183714814', 'city' => 'North Hills', 'inbox_url' => 'https://business.facebook.com/latest/33937789962487126?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1290893252849936', 'created_time' => '2025-12-28T22:37:16-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'candelairan@yahoo.com', 'full_name' => 'Iran Sanchez', 'phone_number' => 'p:+13058076326', 'city' => 'Del Rio', 'inbox_url' => 'https://business.facebook.com/latest/25583313391264918?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:844409395104476', 'created_time' => '2025-12-28T22:05:19-07:00', 'platform' => 'fb', 'availability' => '5-10_days', 'insurance' => 'no', 'email' => 'kdmsr69@gmail.com', 'full_name' => 'Pwee McCarter', 'phone_number' => 'p:+14322029691', 'city' => 'Houston', 'inbox_url' => 'https://business.facebook.com/latest/25740095982356723?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2161113928037923', 'created_time' => '2025-12-28T22:03:43-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'terrencewest99@yahoo.com', 'full_name' => 'Terrence West', 'phone_number' => 'p:+146983337166', 'city' => 'Florissant', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1502451277721423', 'created_time' => '2025-12-28T22:00:41-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'truckingeee@gmail.com', 'full_name' => 'Erland Rayos', 'phone_number' => 'p:+19707494205', 'city' => 'Durango', 'inbox_url' => 'https://business.facebook.com/latest/24596900033323019?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:758986943263801', 'created_time' => '2025-12-28T21:56:28-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'yes', 'email' => 'galvand2012@gmail.com', 'full_name' => 'Daniel Galvan', 'phone_number' => 'p:+19405508938', 'city' => 'Graham', 'inbox_url' => 'https://business.facebook.com/latest/25758364830416284?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1288051123343231', 'created_time' => '2025-12-28T21:53:32-07:00', 'platform' => 'fb', 'availability' => '5-10_days', 'insurance' => 'no', 'email' => 'ruiztransportation@yahoo.com', 'full_name' => 'Pete Ruiz', 'phone_number' => 'p:+19565863670', 'city' => 'San Juan', 'inbox_url' => 'https://business.facebook.com/latest/25466222209655754?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1624351522038273', 'created_time' => '2025-12-28T21:06:43-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'btlad77@yahoo.com', 'full_name' => 'Bolaji', 'phone_number' => 'p:+18324996846', 'city' => 'Richmond', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1371717717755052', 'created_time' => '2025-12-28T20:57:25-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'kendrick.butcher@icloud.com', 'full_name' => 'Kendrick Butcher', 'phone_number' => 'p:+19363233242', 'city' => 'Huntsville', 'inbox_url' => 'https://business.facebook.com/latest/25532166076405457?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1419326806474936', 'created_time' => '2025-12-28T20:45:03-07:00', 'platform' => 'ig', 'availability' => '1-5_days', 'insurance' => 'yes', 'email' => 'BigBearLND25@gmail.com', 'full_name' => 'Daniel Coy', 'phone_number' => 'p:+15126290678', 'city' => 'Buda', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1355482682982912', 'created_time' => '2025-12-28T20:25:28-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'patrickhwright65@gmail.com', 'full_name' => 'Patrick wright', 'phone_number' => 'p:+18595332155', 'city' => 'Lexington', 'inbox_url' => 'https://business.facebook.com/latest/25986568840929593?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:852588224365398', 'created_time' => '2025-12-28T20:22:47-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'tris8910@aol.com', 'full_name' => 'Tristan Dorsey', 'phone_number' => 'p:+18438334984', 'city' => 'Vestal', 'inbox_url' => 'https://business.facebook.com/latest/25600432696236180?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1633438157624289', 'created_time' => '2025-12-28T19:53:34-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'Oblogisticsllc25@gmail.com', 'full_name' => 'Brian manuel Gonzalez', 'phone_number' => 'p:+13257257711', 'city' => 'Laredo', 'inbox_url' => 'https://business.facebook.com/latest/32903081112673661?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1592942558372531', 'created_time' => '2025-12-28T19:35:46-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'Jvesc08@icloud.com', 'full_name' => 'Juan Bautista', 'phone_number' => 'p:+12544331282', 'city' => 'Eastland', 'inbox_url' => 'https://business.facebook.com/latest/24498873109789328?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1555813702310800', 'created_time' => '2025-12-28T19:21:06-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'Westcoastallstarz@yahoo.com', 'full_name' => 'Dwayne Fastlane Thompson', 'phone_number' => 'p:+19099916987', 'city' => 'Los Angeles', 'inbox_url' => 'https://business.facebook.com/latest/32952669731047074?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1373450334181141', 'created_time' => '2025-12-28T19:20:03-07:00', 'platform' => 'ig', 'availability' => '1-5_days', 'insurance' => 'yes', 'email' => 'Edward.d.leal@gmail.com', 'full_name' => 'Edward Leal', 'phone_number' => 'p:3618330189', 'city' => 'Freer', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1209603511129041', 'created_time' => '2025-12-28T19:08:14-07:00', 'platform' => 'ig', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'williamgreen01@bellsouth.net', 'full_name' => 'William Lester Green', 'phone_number' => 'p:+16623039188', 'city' => 'Indianola', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:25058893147144779', 'created_time' => '2025-12-28T19:04:31-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'gatkuoth1985@gmail.com', 'full_name' => 'Joseph Gatkuoth', 'phone_number' => 'p:+12063766914', 'city' => 'Crowley', 'inbox_url' => 'https://business.facebook.com/latest/25100789236259392?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1581464966321810', 'created_time' => '2025-12-28T18:58:29-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'scorpionenterprisesllc@outlook.com', 'full_name' => 'Benny Moore', 'phone_number' => 'p:+17753740227', 'city' => 'Eureka Nevada', 'inbox_url' => 'https://business.facebook.com/latest/24393113297053908?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:763220466075199', 'created_time' => '2025-12-28T18:35:39-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'paulcad2003@yahoo.com', 'full_name' => 'Paul Escalona', 'phone_number' => 'p:+13616521375', 'city' => 'La Victoria', 'inbox_url' => 'https://business.facebook.com/latest/25647742514862016?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1632587087902041', 'created_time' => '2025-12-28T18:32:17-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'oasamoah007@gmail.com', 'full_name' => 'OB1', 'phone_number' => 'p:+15802915669', 'city' => 'Grand Prairie', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1394079159116825', 'created_time' => '2025-12-28T18:02:59-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'yes', 'email' => 'georobinssr@aol.com', 'full_name' => 'George Robinson', 'phone_number' => 'p:+13302330190', 'city' => 'Youngstown', 'inbox_url' => 'https://business.facebook.com/latest/24376300792045742?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1936844467267950', 'created_time' => '2025-12-28T17:59:29-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'charlot_bryan@yahoo.com', 'full_name' => 'Bryan Charlot', 'phone_number' => 'p:2254283218', 'city' => 'Opelousas', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1416299210053047', 'created_time' => '2025-12-28T17:54:19-07:00', 'platform' => 'ig', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'wedreambigtrucking@gmail.com', 'full_name' => 'Im Not Ur Type', 'phone_number' => 'p:+3466282853', 'city' => 'Katy', 'inbox_url' => null, 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2038073177040664', 'created_time' => '2025-12-28T16:54:45-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'yes', 'email' => 'genro4casteneda@gmail.com', 'full_name' => 'Genaro Castaneda', 'phone_number' => 'p:+19795740594', 'city' => 'Caldwell', 'inbox_url' => 'https://business.facebook.com/latest/33767596339498384?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1217108513698531', 'created_time' => '2025-12-28T16:51:47-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'Jdbsolutionz@gmail.com', 'full_name' => 'Jay  Brown', 'phone_number' => 'p:+19855160875', 'city' => 'Hammond', 'inbox_url' => 'https://business.facebook.com/latest/25141737668781471?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:5239473379612034', 'created_time' => '2025-12-28T16:38:02-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'arnoram123@gmail.com', 'full_name' => 'Arnold Ramirez', 'phone_number' => 'p:+13612310463', 'city' => 'Hebbronville', 'inbox_url' => 'https://business.facebook.com/latest/25205382129144186?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1778062279552372', 'created_time' => '2025-12-28T14:54:23-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'ahmad.cummings@yahoo.com', 'full_name' => 'Ahmad Cummings', 'phone_number' => 'p:+18323261533', 'city' => 'Waco', 'inbox_url' => 'https://business.facebook.com/latest/26174806188788076?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1385809472996105', 'created_time' => '2025-12-28T14:36:04-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'yes', 'email' => 'jmelitehotshot@yahoo.com', 'full_name' => 'JoJo Peralta', 'phone_number' => 'p:+18303177971', 'city' => 'Pearsall', 'inbox_url' => 'https://business.facebook.com/latest/24569736939367669?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1209802121118355', 'created_time' => '2025-12-28T13:49:52-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'Danny.777gonzalez9876@gmail.com', 'full_name' => 'Daniel Gonzalez', 'phone_number' => 'p:+18324728115', 'city' => 'North Houston', 'inbox_url' => 'https://business.facebook.com/latest/24719391964334346?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:2278264016006509', 'created_time' => '2025-12-28T13:43:00-07:00', 'platform' => 'fb', 'availability' => '1-5_days', 'insurance' => 'no', 'email' => 'moraisarielso@yahoo.com', 'full_name' => 'Arielso Morais', 'phone_number' => 'p:+14695312889', 'city' => 'Corinth', 'inbox_url' => 'https://business.facebook.com/latest/26031349799795666?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:892700756592324', 'created_time' => '2025-12-28T13:40:59-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'Marquellmartin33@gmail.com', 'full_name' => 'Marquell Martin', 'phone_number' => 'p:+14059673823', 'city' => 'Fort Worth', 'inbox_url' => 'https://business.facebook.com/latest/25269703352681235?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
            ['lead_id' => 'l:1771762436838269', 'created_time' => '2025-12-28T13:39:32-07:00', 'platform' => 'fb', 'availability' => 'in_january', 'insurance' => 'no', 'email' => 'traeyruss@gmail.com', 'full_name' => 'DiTraeyvious Russell', 'phone_number' => 'p:+16627191184', 'city' => 'Sunflower', 'inbox_url' => 'https://business.facebook.com/latest/24103761389309393?nav_ref=thread_view_by_psid', 'is_organic' => 'FALSE'],
        ];

        $records = [];

        foreach ($rows as $row) {
            $payload = array_merge($common, $row);

            $records[] = $this->buildLead([
                'source_name' => 'meta_lead_form_liberty_boxes',
                'ad_name' => $common['ad_name'],
                'platform' => $row['platform'],
                'source_created_at' => $row['created_time'],
                'lead_date_choice' => $row['availability'],
                'insurance_answer' => $row['insurance'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone_number'],
                'city' => $row['city'],
                'state' => null,
                'carrier_class' => null,
                'usdot' => null,
                'notes' => $this->noteLines([
                    'Lead ID: ' . $row['lead_id'],
                    'Campaign: ' . $common['campaign_name'],
                    'Ad Set: ' . $common['adset_name'],
                    'Form: ' . $common['form_name'],
                    'Organic: ' . $row['is_organic'],
                    $row['inbox_url'] ? 'Inbox URL: ' . $row['inbox_url'] : null,
                ]),
                'raw_payload' => $payload,
            ]);
        }

        return $records;
    }

    private function stxHoppersLeads(): array
    {
        $rows = [
            ['created_time' => '12/6/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'Dlpliquids@gmail.com', 'full_name' => 'David Plasek', 'phone' => '+12143991336', 'city' => 'Baytown', 'state' => 'TX'],
            ['created_time' => '12/6/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'wade_cedrick@yahoo.com', 'full_name' => 'Ced Wade', 'phone' => '+19795957730', 'city' => 'Bryan', 'state' => 'TX'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'littlekris1985@yahoo.com', 'full_name' => 'Kris Trevino', 'phone' => '+19792538053', 'city' => 'El Campo', 'state' => 'TX'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'rdiazexpress@gmail.com', 'full_name' => 'Rolando Diaz', 'phone' => '+19565640590', 'city' => 'Santa Rosa', 'state' => 'TX'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'trailhandtransport@hotmail.com', 'full_name' => 'Richard Carter', 'phone' => '+18062526037', 'city' => 'Anton', 'state' => 'TX'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'flores19501978@gmail.com', 'full_name' => 'mike', 'phone' => '+18063402117', 'city' => 'Amarillo', 'state' => 'TX'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'fasv8896@gmail.com', 'full_name' => 'Sanchez A Franklin', 'phone' => '+19548959849', 'city' => 'Kermit', 'state' => 'tx'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'jnmoralesr001@gmail.com', 'full_name' => 'Nelson Romero', 'phone' => '+13464041717', 'city' => 'Houston tx', 'state' => 'TX'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'Gulfcoastnavigation@outlook.com', 'full_name' => 'Dave Warren Taylor Jr', 'phone' => '+14043797317', 'city' => 'Houston', 'state' => 'TX'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'h.z.trucking47@gmail.com', 'full_name' => 'Jesus  hernandez', 'phone' => '+19792401813', 'city' => 'Palacios', 'state' => 'Texas'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'mb46419@yahoo.com', 'full_name' => 'Mack Wilkinson', 'phone' => '+18175044297', 'city' => 'Clifton', 'state' => 'Texas'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'nodamikel117@gmail.com', 'full_name' => 'Maykel Noda Quintero', 'phone' => '+13054310439', 'city' => 'Miami', 'state' => 'Texas'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'hhent@pldi.net', 'full_name' => 'Terry Hunter', 'phone' => '+15805717334', 'city' => 'Fort Supply', 'state' => 'Oklahoma'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'jwvprtruckmail@yahoo.com', 'full_name' => 'Joe Weaver', 'phone' => null, 'city' => 'Enid', 'state' => 'OK'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'redcombineman@yahoo.com', 'full_name' => 'Gene Zelnicek', 'phone' => '+14058530202', 'city' => 'Hennessey', 'state' => 'OK'],
            ['created_time' => '12/6/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'geradh47@cuatrucking.com', 'full_name' => 'Gerardo Holguin', 'phone' => '+15059329066', 'city' => 'Farmington', 'state' => 'NM'],
            ['created_time' => '12/6/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'rodericlewis@yahoo.com', 'full_name' => 'Roderic W Lewis', 'phone' => '+16013926985', 'city' => 'Natchez', 'state' => 'MS'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'hyerj@ymail.com', 'full_name' => 'James Hyer', 'phone' => '+15733780409', 'city' => 'stover', 'state' => 'Missouri'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'scain2974@yahoo.com', 'full_name' => 'Steven Cain', 'phone' => '+14692036016', 'city' => 'Jackson Mississippi', 'state' => 'miss'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'jimmyharris170@gmail.com', 'full_name' => 'Jimmy Harris', 'phone' => '+12256921808', 'city' => 'plaquemine', 'state' => 'Lousiana'],
            ['created_time' => '12/6/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'brucesmith12399@gmail.com', 'full_name' => 'Bruce Smith', 'phone' => '+19859691029', 'city' => 'Roseland', 'state' => 'LA'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'dm4montoya@gmail.com', 'full_name' => 'David Montoya', 'phone' => '+16203909060', 'city' => 'Garden City', 'state' => 'KS'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'lookuptransport@mail.com', 'full_name' => 'William Mcgee', 'phone' => '+12292375424', 'city' => 'lookuptransport@mail.com', 'state' => 'Ga'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => '3sixty5truckinginc@gmail.com', 'full_name' => 'Taiwan Cameron', 'phone' => '+13236831633', 'city' => 'Los Angeles', 'state' => 'CA'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => '1971tommymadden@gmail.com', 'full_name' => 'Tommy Madden', 'phone' => '+18705014057', 'city' => 'McGehee', 'state' => 'Arkansas'],
            ['created_time' => '11/24/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'onealrobert29@gmail.con', 'full_name' => "Robert D O'Neal", 'phone' => '+18708211494', 'city' => 'Marianna', 'state' => 'AR'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'gerald.armstrong32268@gmail.com', 'full_name' => 'Gerald Armstrong', 'phone' => '+18703173396', 'city' => 'North Little Rock', 'state' => 'AR'],
            ['created_time' => '11/23/25', 'campaign_name' => 'STX Hoppers', 'ownhopper' => 'yes', 'email' => 'jjdubb870@gmail.com', 'full_name' => 'Jerry Jordan', 'phone' => '+15013666964', 'city' => 'little rock', 'state' => 'AR'],
        ];

        $records = [];

        foreach ($rows as $row) {
            $records[] = $this->buildLead([
                'source_name' => 'stx_hoppers_csv',
                'ad_name' => $row['campaign_name'],
                'platform' => 'manual_import',
                'source_created_at' => $row['created_time'],
                'lead_date_choice' => null,
                'insurance_answer' => null,
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'city' => $row['city'],
                'state' => $row['state'],
                'carrier_class' => strtolower((string) $row['ownhopper']) === 'yes' ? 'Own Hopper Lead' : null,
                'usdot' => null,
                'notes' => $this->noteLines([
                    'Campaign: ' . $row['campaign_name'],
                    'Own hopper: ' . $row['ownhopper'],
                ]),
                'raw_payload' => $row,
            ]);
        }

        return $records;
    }

    private function buildLead(array $data): array
    {
        $sourceCreatedAt = $this->parseDate($data['source_created_at'] ?? null);
        $createdAt = $sourceCreatedAt ?? now()->format('Y-m-d H:i:s');

        return [
            'source_name' => $data['source_name'] ?? null,
            'ad_name' => $data['ad_name'] ?? null,
            'platform' => $data['platform'] ?? null,
            'source_created_at' => $sourceCreatedAt,
            'lead_date_choice' => $data['lead_date_choice'] ?? null,
            'insurance_answer' => $data['insurance_answer'] ?? null,
            'full_name' => $data['full_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $this->normalizePhone($data['phone'] ?? null),
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'carrier_class' => $data['carrier_class'] ?? null,
            'usdot' => $data['usdot'] ?? null,
            'truck_count' => $data['truck_count'] ?? null,
            'trailer_count' => $data['trailer_count'] ?? null,
            'lead_status' => $data['lead_status'] ?? 'new',
            'notes' => $data['notes'] ?? null,
            'raw_payload' => isset($data['raw_payload'])
                ? json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'linked_carrier_id' => null,
            'assigned_admin_user_id' => null,
            'sold_amount' => null,
            'referral_fee' => null,
            'sold_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'deleted_at' => null,
        ];
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse(trim($value))->format('Y-m-d H:i:s');
    }

    private function normalizePhone(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if (str_starts_with($value, 'p:')) {
            $value = substr($value, 2);
        }

        return trim($value);
    }

    private function noteLines(array $lines): ?string
    {
        $lines = array_values(array_filter($lines, fn ($line) => filled($line)));

        return empty($lines) ? null : implode("\n", $lines);
    }
}
