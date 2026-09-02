<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyType;
use App\Models\AgencyContact;
use App\Models\ContactType;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporaryAgencySeeder extends Seeder
{
    /**
     * Seed temporary agency data for KNOWURLOCAL testing.
     *
     * This seeder intentionally does NOT create or modify agency images.
     * It inserts only agencies and their required Hotline/Email contacts.
     */
    public function run(): void
    {
        /*
         * Resolve the NGA type by its stable name instead of assuming
         * that database ID 1 will always remain the NGA record.
         */
        $ngaTypeId = AgencyType::where('name', 'NGA')->value('id');

        /*
         * Resolve all required category IDs by their stable names.
         *
         * This prevents the seeder from depending on specific
         * auto-increment IDs such as category ID 1 or 2.
         */
        $categoryIds = Category::whereIn('category_name', [

            'Agriculture & Agrarian Services',
            'Education',
            'Public Safety & Security',
            'Transportation & Aviation',
            'Science, Technology & Innovation',
            'Trade, Industry & Enterprise',
            'Financial & Social Security Services',
            'Government & Public Administration',
            'Justice & Legal Services',
            'Social Welfare & Community Services',
            'Health & Public Health',
            'Environment & Natural Resources',
            'Maritime, Ports & Postal Services',
            'Labor & Employment',
            'Taxation & Revenue',

        ])->pluck('id', 'category_name');

        /*
         * Resolve contact-type IDs by their stable slugs.
         *
         * This is safer than hard-coding contact_type_id values because
         * database IDs may change while the semantic slugs remain stable.
         */
        $hotlineTypeId = ContactType::where(
            'slug',
            'hotline'
        )->value('id');

        $emailTypeId = ContactType::where(
            'slug',
            'email'
        )->value('id');

        /*
         * Stop immediately if the NGA reference is missing.
         *
         * This prevents invalid agency_type_id values from being inserted.
         */
        if (!$ngaTypeId) {
            throw new RuntimeException(
                'NGA agency type was not found.'
            );
        }

        /*
         * The agency dataset depends on exactly 15 categories.
         *
         * If even one category is missing, the seeder stops before
         * inserting agencies rather than creating incomplete records.
         */
        if ($categoryIds->count() !== 15) {
            throw new RuntimeException(
                'One or more required agency categories were not found.'
            );
        }

        /*
         * Stop if required contact types are missing.
         */
        if (!$hotlineTypeId || !$emailTypeId) {
            throw new RuntimeException(
                'Required Hotline and Email contact types were not found.'
            );
        }

        /*
         * Temporary test dataset.
         *
         * Coordinates are practical map points within San Jose,
         * Occidental Mindoro. They are suitable for system testing,
         * but should be rechecked before being treated as
         * production-grade survey coordinates.
         *
         * For agencies where a current local public contact was not
         * reliably published, an official national/public-assistance
         * contact is used instead of inventing a local contact.
         */
        $agencies = [

            [
                'agency_name' => 'Department of Agriculture',
                'agency_abbreviation' => 'DA',
                'category' => 'Agriculture & Agrarian Services',
                'agency_description' => 'Government agency responsible for promoting agricultural development and supporting farmers, fisherfolk, agricultural enterprises, and rural communities through agricultural programs and technical assistance.',
                'services_offered' => 'Agricultural assistance; farmer and fisherfolk programs; agricultural production support; farm development; livelihood programs; technical assistance; training and agricultural information.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.068200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'DA Hotline',
                        'value' => '1381',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Official Email',
                        'value' => 'osec.official@da.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Agrarian Reform',
                'agency_abbreviation' => 'DAR',
                'category' => 'Agriculture & Agrarian Services',
                'agency_description' => 'Government agency responsible for implementing agrarian reform programs and assisting qualified farmers with land tenure and support services.',
                'services_offered' => 'Land distribution and land tenure services; agrarian legal assistance; farmer support programs; land use assistance; agrarian reform beneficiary services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'P. Burgos Street, Barangay Pag-asa, San Jose, Occidental Mindoro',
                'lat' => 12.354000,
                'lng' => 121.066000,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Public Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Official Email',
                        'value' => 'contact_us@dar.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'National Irrigation Administration - Occidental Mindoro Irrigation Management Office',
                'agency_abbreviation' => 'NIA',
                'category' => 'Agriculture & Agrarian Services',
                'agency_description' => 'Government agency responsible for developing, operating, and maintaining irrigation systems that support agricultural production.',
                'services_offered' => 'Irrigation development; irrigation system operation and maintenance; water delivery management; irrigators association assistance; irrigation technical services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Airport Road, Barangay San Roque, San Jose, Occidental Mindoro',
                'lat' => 12.361200,
                'lng' => 121.058300,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Office Hotline',
                        'value' => '0977-8247254',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'BAC / Office Email',
                        'value' => 'r4b.occimindoro-imo.bac@nia.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Education - San Jose Sub-office',
                'agency_abbreviation' => 'DepEd',
                'category' => 'Education',
                'agency_description' => 'Government agency responsible for delivering and supporting accessible and quality basic education for learners and communities.',
                'services_offered' => 'Basic education programs; learner enrollment and records assistance; teacher and school support; learner welfare; school governance; education-related information and assistance.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Bonifacio Street, San Jose, Occidental Mindoro',
                'lat' => 12.353000,
                'lng' => 121.066800,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Public Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'DepEd Action Center',
                        'value' => 'action@deped.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Bureau of Fire Protection - San Jose Fire Station',
                'agency_abbreviation' => 'BFP',
                'category' => 'Public Safety & Security',
                'agency_description' => 'Government fire service responsible for fire prevention, fire suppression, rescue operations, emergency response, and fire safety enforcement.',
                'services_offered' => 'Fire emergency response; rescue assistance; fire safety inspection; fire prevention education; fire safety certification-related services.',
                'office_hours' => '24 hours / Emergency response',
                'agency_location' => 'Felix Y. Manalo Avenue, San Jose, Occidental Mindoro',
                'lat' => 12.337500,
                'lng' => 121.070100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Emergency Hotline',
                        'value' => '911',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'National Headquarters Email',
                        'value' => 'nhq@bfp.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'San Jose Municipal Police Station',
                'agency_abbreviation' => 'PNP',
                'category' => 'Public Safety & Security',
                'agency_description' => 'Local police unit responsible for maintaining peace and order, preventing and investigating crimes, responding to emergencies, and protecting the community.',
                'services_offered' => 'Police assistance; crime reporting; emergency response; investigation; public safety assistance; police-related document and clearance assistance.',
                'office_hours' => '24 hours / Emergency response',
                'agency_location' => 'Quezon Street, San Jose, Occidental Mindoro',
                'lat' => 12.352300,
                'lng' => 121.069100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Emergency Hotline',
                        'value' => '911',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'E-Sumbong',
                        'value' => 'e-sumbong@pnp.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Philippine Army',
                'agency_abbreviation' => 'PA',
                'category' => 'Public Safety & Security',
                'agency_description' => 'Land-based branch of the Armed Forces of the Philippines responsible for territorial defense, security operations, and support to civilian authorities when required.',
                'services_offered' => 'Territorial security; community assistance; civil-military coordination; humanitarian assistance; disaster response support.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM; operations as required',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'AFP / Army Contact',
                        'value' => '8911-6001',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'AFP Public Contact',
                        'value' => 'paoafp@gmail.com',
                    ],
                ],
            ],

            [
                'agency_name' => 'Civil Aviation Authority of the Philippines - San Jose Airport',
                'agency_abbreviation' => 'CAAP',
                'category' => 'Transportation & Aviation',
                'agency_description' => 'Government agency responsible for regulating civil aviation and overseeing airport operations and aviation safety.',
                'services_offered' => 'Airport operations; aviation safety and security; passenger and airport assistance; aviation regulatory services.',
                'office_hours' => 'Airport operations / as scheduled',
                'agency_location' => 'San Jose Airport, Barangay San Roque, San Jose, Occidental Mindoro',
                'lat' => 12.366000,
                'lng' => 121.045000,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'CAAP Operations Center',
                        'value' => '(02) 8246-4988 local 2234',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Operations Center',
                        'value' => 'opcen@caap.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Science and Technology - PSTO Occidental Mindoro',
                'agency_abbreviation' => 'DOST',
                'category' => 'Science, Technology & Innovation',
                'agency_description' => 'Government agency that promotes science, technology, research, and innovation to support communities, businesses, and local development.',
                'services_offered' => 'Technology assistance; technical consulting; innovation support; research and development assistance; testing referrals; science and technology training.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Blessings 1 Bldg., F.Y. Manalo Avenue, Barangay Pag-asa, San Jose, Occidental Mindoro',
                'lat' => 12.345000,
                'lng' => 121.068000,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Mobile',
                        'value' => '0920-969-6224',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'PSTO Email',
                        'value' => 'pstc.occimindoro@mimaropa.dost.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Trade and Industry - Occidental Mindoro Provincial Office',
                'agency_abbreviation' => 'DTI',
                'category' => 'Trade, Industry & Enterprise',
                'agency_description' => 'Government agency supporting businesses, entrepreneurs, consumers, and local industries through enterprise development, trade promotion, and consumer protection programs.',
                'services_offered' => 'Business assistance; Negosyo Center services; entrepreneurship training; MSME development; consumer assistance; business advisory services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.353000,
                'lng' => 121.066500,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Customer Contact Center',
                        'value' => '1-DTI (384)',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Customer Contact Center',
                        'value' => 'ask@dti.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Land Bank of the Philippines - San Jose Branch',
                'agency_abbreviation' => 'LANDBANK',
                'category' => 'Financial & Social Security Services',
                'agency_description' => 'Government financial institution providing banking and financing services to individuals, businesses, farmers, and government institutions.',
                'services_offered' => 'Deposits; withdrawals; loans and financing; government transactions; agricultural and MSME financing; payment and banking services.',
                'office_hours' => 'Monday-Friday, 9:00 AM-4:00 PM',
                'agency_location' => 'Punzalan Building, Quirino Street, Barangay 6, San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'San Jose Branch',
                        'value' => '(043) 457-0243',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Branch Email',
                        'value' => 'BR_SNJOSEM@mail.landbank.com',
                    ],
                ],
            ],

            [
                'agency_name' => 'Development Bank of the Philippines - San Jose Branch',
                'agency_abbreviation' => 'DBP',
                'category' => 'Financial & Social Security Services',
                'agency_description' => 'Government financial institution providing banking and development financing services to individuals, businesses, government entities, and priority economic sectors.',
                'services_offered' => 'Deposit accounts; loans; development financing; government banking services; payment services; business financing.',
                'office_hours' => 'Monday-Friday, 9:00 AM-4:00 PM',
                'agency_location' => 'Rizal Street corner Quirino Street, San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'San Jose Branch',
                        'value' => '(043) 491-2073',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Branch Email',
                        'value' => 'sanjose@dbp.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Commission on Elections - San Jose Office',
                'agency_abbreviation' => 'COMELEC',
                'category' => 'Government & Public Administration',
                'agency_description' => 'Constitutional commission responsible for administering elections and maintaining the integrity of the electoral process.',
                'services_offered' => 'Voter registration assistance; voter record concerns; election information; election-related inquiries; electoral process assistance.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.353000,
                'lng' => 121.067000,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Government Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Provincial Election Office',
                        'value' => 'opes_occmindoro@comelec.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of the Interior and Local Government - San Jose',
                'agency_abbreviation' => 'DILG',
                'category' => 'Government & Public Administration',
                'agency_description' => 'Government agency that supports effective local governance, peace and order, public safety, and accountable local government administration.',
                'services_offered' => 'Local governance assistance; barangay governance support; capacity building; local government monitoring; public safety and governance programs.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'San Jose Mobile',
                        'value' => '0917-840-2244',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'San Jose DILG Office',
                        'value' => 'dilgsanjoseocmin@gmail.com',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Social Welfare and Development - Occidental Mindoro SWAD',
                'agency_abbreviation' => 'DSWD',
                'category' => 'Social Welfare & Community Services',
                'agency_description' => 'Government agency providing social protection, welfare assistance, livelihood support, and crisis intervention services to individuals, families, and communities.',
                'services_offered' => 'Social welfare assistance; crisis intervention; livelihood programs; Pantawid Pamilyang Pilipino Program support; emergency assistance; community-based social services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => '2340 Padre Burgos St., Barangay Pag-Asa, San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.068200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'SWAD / SLP Contact',
                        'value' => '(043) 732-0451',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'SWAD Email',
                        'value' => 'ocmindoroswadt.fomimaropa@dswd.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Labor and Employment - Occidental Mindoro Provincial Office',
                'agency_abbreviation' => 'DOLE',
                'category' => 'Labor & Employment',
                'agency_description' => 'Government agency responsible for promoting workers welfare, employment opportunities, labor standards, and fair employment practices.',
                'services_offered' => 'Employment assistance; labor standards assistance; worker welfare programs; livelihood and employment programs; labor dispute assistance; job-seeking support.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.353000,
                'lng' => 121.066500,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Provincial Office',
                        'value' => '(043) 457-0463',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Provincial Office',
                        'value' => 'ro4b_ocmindoro@dole.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Pag-IBIG Fund - San Jose Member Services Office',
                'agency_abbreviation' => 'Pag-IBIG',
                'category' => 'Financial & Social Security Services',
                'agency_description' => 'Government-owned savings and housing finance institution providing savings, housing loans, and other financial programs to members.',
                'services_offered' => 'Membership services; savings programs; housing loans; short-term loans; member account assistance; Pag-IBIG payment services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'National Road corner Calderon Street, Barangay Labangan, San Jose, Occidental Mindoro',
                'lat' => 12.348900,
                'lng' => 121.065700,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Pag-IBIG Hotline',
                        'value' => '8-PAG-IBIG (724-4244)',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Official Email',
                        'value' => 'contactus@pagibigfund.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Social Security System - San Jose Branch',
                'agency_abbreviation' => 'SSS',
                'category' => 'Financial & Social Security Services',
                'agency_description' => 'Government social security institution providing social insurance and benefits to covered workers, employers, and their beneficiaries.',
                'services_offered' => 'SSS membership services; contribution assistance; benefit applications; loans; retirement and disability benefits; maternity and sickness benefit assistance.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Balmes Building, Diego Silang Street, Barangay 5, San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.068200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'SSS Hotline',
                        'value' => '1455',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'San Jose Branch',
                        'value' => 'sanjose@sss.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Civil Service Commission - Occidental Mindoro Field Office',
                'agency_abbreviation' => 'CSC',
                'category' => 'Government & Public Administration',
                'agency_description' => 'Constitutional commission responsible for promoting a merit-based, professional, ethical, and efficient public service.',
                'services_offered' => 'Civil service examination assistance; eligibility services; government employment information; personnel-related assistance; certification and records services.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'CSC Building, Hidalgo Street, Barangay 7, San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.068200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Field Office',
                        'value' => '(043) 457-9091',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Field Office',
                        'value' => 'ro04.fo_occidentalmindoro@csc.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Regional Trial Court - San Jose, Occidental Mindoro',
                'agency_abbreviation' => 'RTC / SC',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'Court that hears and decides civil, criminal, and other cases within its jurisdiction under the Philippine judicial system.',
                'services_offered' => 'Court proceedings; case filing and processing; court documents; judicial records; hearings and case-related transactions.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Hall of Justice, San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.069100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Judiciary Public Assistance',
                        'value' => '+63 2 8552-9644',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Judiciary Public Assistance',
                        'value' => 'chiefjusticehelpdesk@judiciary.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'National Bureau of Investigation - San Jose Satellite Office',
                'agency_abbreviation' => 'NBI',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'National investigative agency responsible for investigating major crimes and providing investigative, forensic, and clearance-related services.',
                'services_offered' => 'NBI clearance assistance; criminal investigation; forensic services; background investigation; investigative assistance.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM; satellite schedules may vary',
                'agency_location' => 'Sangguniang Bayan Building, Municipal Compound, San Jose, Occidental Mindoro',
                'lat' => 12.353000,
                'lng' => 121.066800,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Public Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'MIMAROPA NBI',
                        'value' => 'mimaropa@nbi.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => "Public Attorney's Office - San Jose District Office",
                'agency_abbreviation' => 'PAO',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'Government legal assistance office providing free legal services to qualified individuals who cannot afford private legal representation.',
                'services_offered' => 'Free legal consultation; legal representation; preparation of legal documents; assistance in criminal, civil, family, and other qualified cases.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.069100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'PAO Central Office',
                        'value' => '(02) 8929-9436',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'PAO Email',
                        'value' => 'pao@pao.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Bureau of Jail Management and Penology - San Jose District Jail',
                'agency_abbreviation' => 'BJMP',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'Government agency responsible for the administration, custody, security, and rehabilitation of persons deprived of liberty in local jails.',
                'services_offered' => 'Jail management; custody and security; rehabilitation programs; visitation assistance; welfare and development programs for persons deprived of liberty.',
                'office_hours' => '24 hours / Jail operations',
                'agency_location' => 'San Jose District Jail, San Jose, Occidental Mindoro',
                'lat' => 12.369400,
                'lng' => 121.102900,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Emergency Contact',
                        'value' => '911',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'BJMP General Contact',
                        'value' => 'bjmp@bjmp.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Justice - Occidental Mindoro',
                'agency_abbreviation' => 'DOJ',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'Government department responsible for the administration of justice and prosecution of criminal cases and other legal matters within its mandate.',
                'services_offered' => 'Prosecution services; preliminary investigation; legal assistance and advice; case evaluation; criminal complaint processing.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.069100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'DOJ Main Office',
                        'value' => '(02) 8523-8481',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'DOJ Action Center',
                        'value' => 'dojac@doj.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Philippine Charity Sweepstakes Office - Occidental Mindoro Branch',
                'agency_abbreviation' => 'PCSO',
                'category' => 'Social Welfare & Community Services',
                'agency_description' => 'Government-owned corporation that raises funds through charity games and provides financial and medical assistance programs.',
                'services_offered' => 'Medical assistance; financial assistance; charity programs; individual assistance; support for qualified health-related expenses.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Barangay Central, San Jose, Occidental Mindoro',
                'lat' => 12.350600,
                'lng' => 121.065700,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Occidental Mindoro Branch',
                        'value' => '(043) 732-0184',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Branch Email',
                        'value' => 'occidentalmindorobranch@pcso.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Environment and Natural Resources - CENRO San Jose',
                'agency_abbreviation' => 'DENR',
                'category' => 'Environment & Natural Resources',
                'agency_description' => 'Government agency responsible for environmental protection and the sustainable management, conservation, and regulation of natural resources.',
                'services_offered' => 'Environmental assistance; forestry services; natural resource management; permits and clearances; conservation programs; environmental information.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'National Highway, Barangay Labangan, San Jose, Occidental Mindoro',
                'lat' => 12.354800,
                'lng' => 121.070800,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'DENR Action Hotline',
                        'value' => '#DENR / #3367',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Action Center',
                        'value' => 'aksyonkalikasan@denr.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Department of Health - Occidental Mindoro',
                'agency_abbreviation' => 'DOH',
                'category' => 'Health & Public Health',
                'agency_description' => 'Government agency responsible for protecting and promoting public health, disease prevention, health regulation, and healthcare system support.',
                'services_offered' => 'Public health programs; disease prevention; health education; health regulation; health facility support; health emergency assistance.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'DOH Hotline',
                        'value' => '1555',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'DOH Action Center',
                        'value' => 'actioncenter@doh.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Philippine Postal Corporation - San Jose Post Office',
                'agency_abbreviation' => 'PhilPost',
                'category' => 'Maritime, Ports & Postal Services',
                'agency_description' => 'Government corporation providing postal and mail-related services to individuals, businesses, and government offices.',
                'services_offered' => 'Mail acceptance and delivery; parcel services; registered mail; postal documentation; other postal transactions.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Municipal Compound, Barangay 7, San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.068200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Customer Care',
                        'value' => '(02) 8288-7678',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Customer Care',
                        'value' => 'phlpostcares@phlpost.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Cooperative Development Authority - Occidental Mindoro',
                'agency_abbreviation' => 'CDA',
                'category' => 'Trade, Industry & Enterprise',
                'agency_description' => 'Government agency responsible for promoting, registering, regulating, and supporting cooperatives in the Philippines.',
                'services_offered' => 'Cooperative registration; development assistance; training; compliance assistance; organizational development; cooperative monitoring.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Sub-Capitol Compound, Barangay Magbay, San Jose, Occidental Mindoro',
                'lat' => 12.339300,
                'lng' => 121.070100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'CDA Helpdesk',
                        'value' => '(02) 8725-3764',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'CDA Helpdesk',
                        'value' => 'helpdesk@cda.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'National Commission on Indigenous Peoples - Occidental Mindoro',
                'agency_abbreviation' => 'NCIP',
                'category' => 'Social Welfare & Community Services',
                'agency_description' => 'Government agency responsible for protecting and promoting the rights, welfare, and interests of Indigenous Cultural Communities and Indigenous Peoples.',
                'services_offered' => 'Ancestral domain assistance; Indigenous Peoples rights protection; documentation and certification; community development assistance; cultural and legal support.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Old National Highway, San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Government Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'NCIP ICTD',
                        'value' => 'ictd@ncip.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Bureau of Corrections - MIMAROPA / Occidental Mindoro',
                'agency_abbreviation' => 'BuCor',
                'category' => 'Justice & Legal Services',
                'agency_description' => 'Government agency responsible for the safekeeping and rehabilitation of national prisoners and the administration of national correctional institutions.',
                'services_offered' => 'Correctional management; rehabilitation; inmate welfare programs; livelihood and skills development; reintegration support.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM / facility operations as required',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.352900,
                'lng' => 121.066200,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'Government Assistance',
                        'value' => '8888',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Office of the Director General',
                        'value' => 'odg@bucor.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Philippine Coast Guard - San Jose Sub-Station',
                'agency_abbreviation' => 'PCG',
                'category' => 'Maritime, Ports & Postal Services',
                'agency_description' => 'Maritime law enforcement and safety agency responsible for maritime security, search and rescue, marine environmental protection, and sea emergency response.',
                'services_offered' => 'Search and rescue; maritime safety assistance; emergency response; maritime law enforcement; marine environmental protection.',
                'office_hours' => '24 hours / Emergency response',
                'agency_location' => 'San Jose, Occidental Mindoro',
                'lat' => 12.327500,
                'lng' => 121.087500,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'San Jose Sub-Station',
                        'value' => '0962-440-4780',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'PCG Contact',
                        'value' => 'cgcommandcenter@coastguard.gov.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Philippine Ports Authority - San Jose Port',
                'agency_abbreviation' => 'PPA',
                'category' => 'Maritime, Ports & Postal Services',
                'agency_description' => 'Government agency responsible for planning, development, regulation, and management of public ports and port facilities.',
                'services_offered' => 'Port operations; passenger and cargo coordination; port facility management; shipping-related assistance; port safety and security coordination.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM; port operations as scheduled',
                'agency_location' => 'PPA Port, Felix Y. Manalo Avenue, San Jose, Occidental Mindoro',
                'lat' => 12.327500,
                'lng' => 121.087500,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'PPA Helpdesk',
                        'value' => '0961-4505935',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'PPA Helpdesk',
                        'value' => 'helpdesk_official@ppa.com.ph',
                    ],
                ],
            ],

            [
                'agency_name' => 'Bureau of Internal Revenue - Revenue District Office No. 37',
                'agency_abbreviation' => 'BIR',
                'category' => 'Taxation & Revenue',
                'agency_description' => 'Government agency responsible for administering and enforcing national internal revenue laws and collecting national taxes.',
                'services_offered' => 'Taxpayer registration; tax filing assistance; tax payment information; taxpayer inquiries; tax compliance services; tax document processing.',
                'office_hours' => 'Monday-Friday, 8:00 AM-5:00 PM',
                'agency_location' => 'Zapeda Building, Liboro Street, Barangay Poblacion IV, San Jose, Occidental Mindoro',
                'lat' => 12.353100,
                'lng' => 121.062100,
                'contacts' => [
                    [
                        'type' => 'hotline',
                        'label' => 'RDO 37',
                        'value' => '(043) 732-1678',
                    ],
                    [
                        'type' => 'email',
                        'label' => 'RDO 37 CSS',
                        'value' => 'rdo_37css@bir.gov.ph',
                    ],
                ],
            ],
        ];

        /*
         * Counters used to provide a simple Artisan console summary.
         */
        $inserted = 0;
        $skipped = 0;

        /*
         * Use one transaction for the complete dataset.
         *
         * If an agency or contact insertion fails, Laravel rolls
         * the entire operation back instead of leaving partial data.
         */
        DB::transaction(function () use (
            $agencies,
            $ngaTypeId,
            $categoryIds,
            $hotlineTypeId,
            $emailTypeId,
            &$inserted,
            &$skipped
        ) {

            /*
             * Process every agency in the temporary dataset.
             */
            foreach ($agencies as $data) {

                /*
                 * Check both active and soft-deleted records.
                 *
                 * This prevents duplicate agencies from being created
                 * when an older record is currently in the Trash.
                 */
                $existingAgency = Agency::withTrashed()
                    ->where(
                        'agency_name',
                        $data['agency_name']
                    )
                    ->first();

                /*
                 * Leave an existing agency untouched.
                 */
                if ($existingAgency) {
                    $skipped++;

                    continue;
                }

                /*
                 * Resolve the category assigned to this agency.
                 *
                 * The agency stores the actual database ID, while the
                 * dataset stores the human-readable category name.
                 */
                $categoryId = $categoryIds->get(
                    $data['category']
                );

                /*
                 * Stop immediately if an agency references a category
                 * that was not loaded from the database.
                 */
                if (!$categoryId) {
                    throw new RuntimeException(
                        "Category '{$data['category']}' was not found for {$data['agency_name']}."
                    );
                }

                /*
                 * Create the agency without an image.
                 *
                 * agency_image is intentionally omitted so this seeder
                 * does not interfere with the existing image-upload system.
                 */
                $agency = Agency::create([
                    'agency_name' => $data['agency_name'],
                    'agency_abbreviation' => $data['agency_abbreviation'],
                    'agency_type_id' => $ngaTypeId,
                    'category_id' => $categoryId,
                    'agency_location' => $data['agency_location'],
                    'agency_description' => $data['agency_description'],
                    'services_offered' => $data['services_offered'],
                    'office_hours' => $data['office_hours'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                ]);

                /*
                 * Insert the agency's required Hotline and Email contacts.
                 */
                foreach ($data['contacts'] as $index => $contact) {

                    /*
                     * Convert the semantic contact type into its
                     * corresponding database ID.
                     */
                    $contactTypeId = match ($contact['type']) {

                        'hotline' => $hotlineTypeId,

                        'email' => $emailTypeId,

                        default => throw new RuntimeException(
                            'Unsupported contact type in temporary agency dataset.'
                        ),
                    };

                    /*
                     * Create the contact associated with the agency.
                     */
                    AgencyContact::create([
                        'agency_id' => $agency->id,
                        'contact_type_id' => $contactTypeId,
                        'label' => $contact['label'],
                        'value' => $contact['value'],
                        'is_primary' => true,
                        'sort_order' => $index + 1,
                    ]);
                }

                /*
                 * Count the successfully inserted agency.
                 */
                $inserted++;
            }
        });

        /*
         * Display the result in the Artisan console.
         */
        $this->command->info(
            'Temporary agency seeder completed.'
        );

        $this->command->info(
            "Inserted: {$inserted}"
        );

        $this->command->info(
            "Skipped existing: {$skipped}"
        );
    }
}