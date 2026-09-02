<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporaryFaqSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Each FAQ is written around a specific citizen task,
         * requirement, eligibility condition, procedure,
         * fee, processing concern, or problem.
         *
         * The agency is identified by its actual database name
         * instead of a hard-coded numeric ID.
         */
        $faqs = [

            /*
             * =========================================================
             * DEPARTMENT OF AGRICULTURE
             * =========================================================
             */

            [
                'agency' => 'Department of Agriculture',

                'question' => 'What documents do I need to register for RSBSA?',

                'answer' => 'For RSBSA registration, prepare the accomplished RSBSA Enrollment Form and the documents applicable to your farmer, farm worker, fisherfolk, or agricultural activity. The DA RSBSA materials identify proof of identity and, depending on the situation, documents related to land tenure or farming activity. If you are a tenant farmer, prepare proof of tenancy. Confirm the documents applicable to your situation with the local agriculture office before submitting.',

                'question_fil' => 'Anong mga dokumento ang kailangan para magparehistro sa RSBSA?',

                'answer_fil' => 'Para sa RSBSA registration, ihanda ang accomplished RSBSA Enrollment Form at mga dokumentong naaangkop sa iyong status bilang farmer, farm worker, fisherfolk, o iba pang agricultural worker. Kabilang sa DA RSBSA materials ang proof of identity at, depende sa sitwasyon, mga dokumento tungkol sa land tenure o farming activity. Kung tenant farmer ka, maghanda ng proof of tenancy. Kumpirmahin muna sa local agriculture office ang eksaktong dokumentong kailangan para sa iyong sitwasyon.',

                'keywords' => 'DA, RSBSA, register RSBSA, registration requirements, RSBSA requirements, documents, farmer registration, farmer, farm worker, fisherfolk',
            ],

            [
                'agency' => 'Department of Agriculture',

                'question' => 'Can I register for RSBSA if I am only a tenant farmer?',

                'answer' => 'Yes. The DA RSBSA FAQ states that tenant farmers may register. A tenant farmer should present proof of tenancy during registration.',

                'question_fil' => 'Maaari ba akong magparehistro sa RSBSA kahit tenant farmer lang ako?',

                'answer_fil' => 'Oo. Ayon sa DA RSBSA FAQ, maaaring magparehistro ang tenant farmers. Kailangang magpakita ng proof of tenancy kapag nagparehistro.',

                'keywords' => 'DA, RSBSA, tenant farmer, tenant, tenancy, register, registration, eligibility, farmer',
            ],

            [
                'agency' => 'Department of Agriculture',

                'question' => 'Can I register for RSBSA if I am a farm worker but do not own farmland?',

                'answer' => 'Yes. The DA RSBSA FAQ states that farm workers can register. Ownership of farmland is not required simply because you are registering as a farm worker.',

                'question_fil' => 'Maaari ba akong magparehistro sa RSBSA kung farm worker ako pero wala akong sariling lupa?',

                'answer_fil' => 'Oo. Ayon sa DA RSBSA FAQ, maaaring magparehistro ang farm workers. Hindi kailangang ikaw ang may-ari ng farmland para makapagparehistro bilang farm worker.',

                'keywords' => 'DA, RSBSA, farm worker, no land, no farmland, registration, eligibility, farmer, register',
            ],

            /*
             * =========================================================
             * DEPARTMENT OF AGRARIAN REFORM
             * =========================================================
             */

            [
                'agency' => 'Department of Agrarian Reform',

                'question' => 'Can DAR help me with an agrarian reform legal concern?',

                'answer' => 'Yes. DAR provides agrarian legal assistance as part of its agrarian justice services. The appropriate DAR office can determine whether your concern falls within its legal assistance services and what documents are needed for your particular case.',

                'question_fil' => 'Maaari ba akong tulungan ng DAR sa legal na concern tungkol sa agrarian reform?',

                'answer_fil' => 'Oo. Nagbibigay ang DAR ng agrarian legal assistance bilang bahagi ng mga serbisyong may kaugnayan sa agrarian justice. Ang kaukulang DAR office ang tutukoy kung sakop ng legal assistance nito ang iyong concern at kung anong dokumento ang kailangan para sa iyong kaso.',

                'keywords' => 'DAR, legal assistance, agrarian legal assistance, agrarian reform, agrarian case, legal concern, farmer, ARB',
            ],

            [
                'agency' => 'Department of Agrarian Reform',

                'question' => 'What should I prepare before asking DAR about my agrarian case?',

                'answer' => 'Prepare the documents and information you already have that are directly related to your agrarian concern, such as land, tenancy, or case records. DAR will determine which documents are required for the specific service or case. Avoid submitting unrelated documents unless requested by the office.',

                'question_fil' => 'Ano ang dapat kong ihanda bago magtanong sa DAR tungkol sa aking agrarian case?',

                'answer_fil' => 'Ihanda ang mga dokumento at impormasyon na mayroon ka na direktang may kaugnayan sa iyong agrarian concern, gaya ng land, tenancy, o case records. Tutukuyin ng DAR kung aling dokumento ang kailangan para sa partikular na serbisyo o kaso. Huwag magsumite ng mga dokumentong walang kaugnayan maliban kung hihingin ng opisina.',

                'keywords' => 'DAR, agrarian case, documents, requirements, land documents, tenancy, case records, legal assistance',
            ],

            /*
             * =========================================================
             * NATIONAL IRRIGATION ADMINISTRATION
             * =========================================================
             */

            [
                'agency' => 'National Irrigation Administration - Occidental Mindoro Irrigation Management Office',

                'question' => 'How do I request water delivery from NIA?',

                'answer' => 'NIA lists Request for Water Delivery as a frontline service. The request is submitted to the appropriate NIA Irrigation Management Office, with the applicable requirements identified in the Citizen\'s Charter. If the request is connected to an Irrigators Association, coordinate with the association and the concerned NIA office before submitting.',

                'question_fil' => 'Paano ako makakahingi ng water delivery mula sa NIA?',

                'answer_fil' => 'Nakasaad sa Citizen\'s Charter ng NIA ang Request for Water Delivery bilang frontline service. Ang request ay isinusumite sa kaukulang NIA Irrigation Management Office kasama ang applicable requirements. Kung konektado ang request sa Irrigators Association, makipag-ugnayan muna sa association at sa concerned NIA office bago magsumite.',

                'keywords' => 'NIA, water delivery, request water, irrigation, irrigation service, farmer, Irrigators Association, IA',
            ],

            [
                'agency' => 'National Irrigation Administration - Occidental Mindoro Irrigation Management Office',

                'question' => 'Can I request repair of an irrigation facility from NIA?',

                'answer' => 'Yes. NIA Region IV-B lists Request for Repair of Irrigation Facilities as one of its frontline services. The appropriate NIA office will determine the applicable procedure and requirements based on the irrigation facility and the nature of the problem.',

                'question_fil' => 'Maaari ba akong humiling ng repair ng irrigation facility sa NIA?',

                'answer_fil' => 'Oo. Nakalista sa NIA Region IV-B Citizen\'s Charter ang Request for Repair of Irrigation Facilities bilang frontline service. Tutukuyin ng kaukulang NIA office ang proseso at requirements depende sa irrigation facility at sa uri ng problema.',

                'keywords' => 'NIA, irrigation repair, repair irrigation facility, irrigation facility, broken irrigation, farmer, irrigation service',
            ],

            /*
             * =========================================================
             * DEPARTMENT OF EDUCATION
             * =========================================================
             */

            [
                'agency' => 'Department of Education - San Jose Sub-office',

                'question' => 'What do I need to enroll a new learner in school?',

                'answer' => 'DepEd requires the appropriate enrollment forms and the complete requirements applicable to the learner\'s grade level and enrollment category. Requirements may differ for new learners, old learners, transferees, and other situations. Confirm the current checklist with the school before submitting.',

                'question_fil' => 'Ano ang kailangan para ma-enroll ang bagong learner sa school?',

                'answer_fil' => 'Kailangan ng DepEd ang tamang enrollment forms at kumpletong requirements na naaangkop sa grade level at enrollment category ng learner. Maaaring magkaiba ang requirements para sa new learners, old learners, transferees, at iba pang sitwasyon. Kumpirmahin muna sa school ang kasalukuyang checklist bago magsumite.',

                'keywords' => 'DepEd, enrollment, enroll, new learner, school enrollment, requirements, student, registration',
            ],

            [
                'agency' => 'Department of Education - San Jose Sub-office',

                'question' => 'What happens if I cannot complete all the enrollment requirements?',

                'answer' => 'DepEd\'s Citizen\'s Charter provides for temporary enrollment in applicable situations when requirements are incomplete. The school will determine the appropriate enrollment status and any undertaking or remaining documents that must be submitted.',

                'question_fil' => 'Ano ang mangyayari kung hindi ko makumpleto lahat ng enrollment requirements?',

                'answer_fil' => 'May probisyon ang DepEd Citizen\'s Charter para sa temporary enrollment sa mga sitwasyong hindi kumpleto ang requirements. Tutukuyin ng school ang tamang enrollment status at kung anong undertaking o natitirang dokumento ang kailangang isumite.',

                'keywords' => 'DepEd, incomplete requirements, enrollment, temporary enrollment, school, learner, missing documents',
            ],

            [
                'agency' => 'Department of Education - San Jose Sub-office',

                'question' => 'How long does enrollment processing take according to DepEd?',

                'answer' => 'DepEd\'s 2025 Citizen\'s Charter lists approximately 1 day, 1 hour, and 40 minutes for the enrollment activities for old learners and approximately 3 days and 40 minutes for new learners. These are the processing times stated in the charter and may depend on the applicable enrollment category and school process.',

                'question_fil' => 'Gaano katagal ang enrollment processing ayon sa DepEd?',

                'answer_fil' => 'Sa 2025 Citizen\'s Charter ng DepEd, nakasaad ang humigit-kumulang 1 araw, 1 oras, at 40 minuto para sa enrollment activities ng old learners at humigit-kumulang 3 araw at 40 minuto para sa new learners. Ito ang processing times na nakasaad sa charter at maaaring depende sa enrollment category at proseso ng school.',

                'keywords' => 'DepEd, enrollment processing time, enrollment duration, old learner, new learner, school enrollment, processing',
            ],

            /*
             * =========================================================
             * BUREAU OF FIRE PROTECTION
             * =========================================================
             */

            [
                'agency' => 'Bureau of Fire Protection - San Jose Fire Station',

                'question' => 'What documents do I need to apply for an FSIC for a Certificate of Occupancy?',

                'answer' => 'For the simple FSIC application for a Certificate of Occupancy described in the BFP Citizen\'s Charter, requirements include the accomplished FSIC application form, an endorsement from the Office of the Building Official, a Certificate of Completion, a certified true copy of the assessment fee for the Occupancy Permit, and an as-built plan when applicable. Additional documents may apply depending on the project.',

                'question_fil' => 'Anong mga dokumento ang kailangan para sa FSIC application para sa Certificate of Occupancy?',

                'answer_fil' => 'Para sa simple FSIC application para sa Certificate of Occupancy na nakasaad sa BFP Citizen\'s Charter, kabilang sa requirements ang accomplished FSIC application form, endorsement mula sa Office of the Building Official, Certificate of Completion, certified true copy ng assessment fee para sa Occupancy Permit, at as-built plan kung applicable. Maaari pang magkaroon ng karagdagang dokumento depende sa proyekto.',

                'keywords' => 'BFP, FSIC, FSIC requirements, Certificate of Occupancy, fire safety inspection, documents, application',
            ],

            [
                'agency' => 'Bureau of Fire Protection - San Jose Fire Station',

                'question' => 'How long does the FSIC application for a Certificate of Occupancy take?',

                'answer' => 'The BFP Citizen\'s Charter lists three working days for the simple FSIC application for a Certificate of Occupancy covered by that service entry.',

                'question_fil' => 'Gaano katagal ang FSIC application para sa Certificate of Occupancy?',

                'answer_fil' => 'Ayon sa BFP Citizen\'s Charter, tatlong working days ang nakalistang processing time para sa simple FSIC application para sa Certificate of Occupancy na sakop ng service entry na iyon.',

                'keywords' => 'BFP, FSIC, processing time, Certificate of Occupancy, three working days, fire safety',
            ],

            /*
             * =========================================================
             * PHILIPPINE NATIONAL POLICE
             * =========================================================
             */

            [
                'agency' => 'San Jose Municipal Police Station',

                'question' => 'What do I need to apply for a National Police Clearance?',

                'answer' => 'PNP Citizen\'s Charter materials for National Police Clearance list a valid ID, proof of payment, and the application reference number among the requirements. The process begins with online registration, completion of the required information, and appointment scheduling through the National Police Clearance System before proceeding to the selected police station.',

                'question_fil' => 'Ano ang kailangan para mag-apply ng National Police Clearance?',

                'answer_fil' => 'Sa PNP Citizen\'s Charter materials para sa National Police Clearance, kabilang sa requirements ang valid ID, proof of payment, at application reference number. Nagsisimula ang proseso sa online registration, pag-complete ng kinakailangang impormasyon, at appointment scheduling sa National Police Clearance System bago pumunta sa napiling police station.',

                'keywords' => 'PNP, National Police Clearance, police clearance, requirements, valid ID, proof of payment, reference number, NPCS',
            ],

            [
                'agency' => 'San Jose Municipal Police Station',

                'question' => 'Do I need to set an appointment before getting a National Police Clearance?',

                'answer' => 'Yes. PNP Citizen\'s Charter materials for National Police Clearance describe online registration and appointment setting as part of the application process. Applicants should complete the required online steps before proceeding to their selected police station.',

                'question_fil' => 'Kailangan ko bang magpa-appointment bago kumuha ng National Police Clearance?',

                'answer_fil' => 'Oo. Sa PNP Citizen\'s Charter materials para sa National Police Clearance, bahagi ng proseso ang online registration at appointment setting. Dapat munang kumpletuhin ang kinakailangang online steps bago pumunta sa napiling police station.',

                'keywords' => 'PNP, National Police Clearance, appointment, police clearance, online registration, NPCS, police station',
            ],

            /*
             * =========================================================
             * PHILIPPINE ARMY
             * =========================================================
             */

            [
                'agency' => 'Philippine Army',

                'question' => 'What are the basic qualifications to apply as a Philippine Army enlisted personnel?',

                'answer' => 'The Philippine Army recruitment materials state that enlisted personnel applicants must meet the applicable educational, age, height, marital-status, and physical requirements and must pass the AFP Aptitude Test Battery. The exact qualifications depend on the recruitment category and current recruitment rules, so applicants should verify the current announcement before applying.',

                'question_fil' => 'Ano ang basic qualifications para mag-apply bilang enlisted personnel sa Philippine Army?',

                'answer_fil' => 'Ayon sa Philippine Army recruitment materials, kailangang matugunan ng enlisted personnel applicants ang applicable educational, age, height, marital-status, at physical requirements at kailangang makapasa sa AFP Aptitude Test Battery. Depende sa recruitment category at kasalukuyang rules ang eksaktong qualifications kaya dapat kumpirmahin muna ang current recruitment announcement bago mag-apply.',

                'keywords' => 'Philippine Army, Army recruitment, enlisted personnel, candidate soldier, qualifications, requirements, AFPATB, military application',
            ],

            [
                'agency' => 'Philippine Army',

                'question' => 'What documents should I prepare for a Philippine Army recruitment application?',

                'answer' => 'Philippine Army recruitment materials list documents such as an authenticated birth certificate, authenticated transcript of records, college diploma when applicable, AFP Aptitude Test Battery result, NBI clearance, and other documents depending on the applicant category. Applicants should use the current official recruitment checklist because requirements may differ between officer, enlisted, and civilian positions.',

                'question_fil' => 'Anong mga dokumento ang dapat kong ihanda para sa Philippine Army recruitment application?',

                'answer_fil' => 'Sa Philippine Army recruitment materials, kabilang sa mga dokumento ang authenticated birth certificate, authenticated transcript of records, college diploma kung applicable, AFP Aptitude Test Battery result, NBI clearance, at iba pang dokumento depende sa applicant category. Gamitin ang kasalukuyang official recruitment checklist dahil maaaring magkaiba ang requirements para sa officer, enlisted, at civilian positions.',

                'keywords' => 'Philippine Army, recruitment requirements, documents, birth certificate, transcript, diploma, NBI clearance, AFPATB',
            ],

            /*
             * =========================================================
             * CIVIL AVIATION AUTHORITY OF THE PHILIPPINES
             * =========================================================
             */

            [
                'agency' => 'Civil Aviation Authority of the Philippines - San Jose Airport',

                'question' => 'Where can I check the requirements for a CAAP aviation transaction?',

                'answer' => 'CAAP publishes its current Citizen\'s Charter containing the requirements and procedures for its aviation-related transactions. The applicable requirements depend on the specific transaction, such as airmen examinations, licenses, certificates, or other aviation regulatory services. Identify the exact transaction first so the correct checklist can be followed.',

                'question_fil' => 'Saan ko makikita ang requirements para sa CAAP aviation transaction?',

                'answer_fil' => 'Naglalathala ang CAAP ng kasalukuyang Citizen\'s Charter na naglalaman ng requirements at procedures para sa aviation-related transactions nito. Depende sa partikular na transaction ang requirements, gaya ng airmen examinations, licenses, certificates, o iba pang aviation regulatory services. Tukuyin muna ang eksaktong transaction upang masunod ang tamang checklist.',

                'keywords' => 'CAAP, aviation transaction, requirements, Citizen Charter, airmen examination, license, certificate, aviation',
            ],

            /*
             * =========================================================
             * DEPARTMENT OF SCIENCE AND TECHNOLOGY
             * =========================================================
             */

            [
                'agency' => 'Department of Science and Technology - PSTO Occidental Mindoro',

                'question' => 'Can my small business apply for DOST SETUP assistance?',

                'answer' => 'DOST-MIMAROPA states that SETUP is intended to assist qualified MSMEs and other eligible entities in addressing technological needs and improving productivity. Eligible applicants include Philippine-based firms wholly owned by Filipino citizens and businesses willing to adopt technological improvements. The application is subject to the program\'s requirements and evaluation.',

                'question_fil' => 'Maaari bang mag-apply ang small business ko para sa DOST SETUP assistance?',

                'answer_fil' => 'Ayon sa DOST-MIMAROPA, ang SETUP ay para sa qualified MSMEs at iba pang eligible entities na nangangailangan ng tulong sa technology at productivity improvement. Kabilang sa eligible applicants ang Philippine-based firms na wholly owned ng Filipino citizens at mga negosyong handang mag-adopt ng technological improvements. Kailangang matugunan ang program requirements at evaluation.',

                'keywords' => 'DOST, SETUP, MSME, small business, technology assistance, business assistance, technology upgrading, application',
            ],

            [
                'agency' => 'Department of Science and Technology - PSTO Occidental Mindoro',

                'question' => 'What documents are needed to apply for DOST SETUP assistance?',

                'answer' => 'DOST-MIMAROPA lists a full project proposal, technical, marketing, management or administrative, and financial information, business permits and licenses, business registration, and other applicable documents among the SETUP requirements. A board resolution may be required for applicable business structures, and three supplier quotations are required when equipment is needed.',

                'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply sa DOST SETUP assistance?',

                'answer_fil' => 'Kabilang sa mga SETUP requirements ng DOST-MIMAROPA ang full project proposal, technical, marketing, management o administrative, at financial information, business permits and licenses, business registration, at iba pang applicable documents. Maaari ring kailanganin ang board resolution depende sa business structure, at tatlong supplier quotations kung kailangan ng equipment.',

                'keywords' => 'DOST, SETUP requirements, documents, project proposal, business permit, registration, supplier quotation, MSME',
            ],

            /*
             * =========================================================
             * DEPARTMENT OF TRADE AND INDUSTRY
             * =========================================================
             */

            [
                'agency' => 'Department of Trade and Industry - Occidental Mindoro Provincial Office',

                'question' => 'What do I need to register a business name with DTI?',

                'answer' => 'For Business Name Registration, DTI requires the complete submission of the applicable requirements under its registration rules. For a Filipino individual applicant, the applicant must be at least 18 years old. A valid government-issued ID is required for the application, and additional documents may apply when a representative is filing or when the applicant falls under another registration category.',

                'question_fil' => 'Ano ang kailangan para mag-register ng business name sa DTI?',

                'answer_fil' => 'Para sa Business Name Registration, kailangang kumpletuhin ang applicable requirements ayon sa DTI registration rules. Para sa Filipino individual applicant, dapat ay hindi bababa sa 18 taong gulang. Kailangan ang valid government-issued ID at maaaring magkaroon ng karagdagang dokumento kung representative ang magfa-file o kung ibang registration category ang applicable.',

                'keywords' => 'DTI, business name registration, business name, requirements, valid ID, registration, negosyo, application',
            ],

            [
                'agency' => 'Department of Trade and Industry - Occidental Mindoro Provincial Office',

                'question' => 'Can I register a business name with DTI if I am below 18 years old?',

                'answer' => 'For Filipino applicants, DTI\'s registration rules state that the applicant must be at least 18 years old to apply for Business Name Registration.',

                'question_fil' => 'Maaari ba akong mag-register ng business name sa DTI kung wala pa akong 18 taong gulang?',

                'answer_fil' => 'Para sa Filipino applicants, nakasaad sa DTI registration rules na kailangang hindi bababa sa 18 taong gulang ang aplikante upang makapag-apply para sa Business Name Registration.',

                'keywords' => 'DTI, business name, registration, age requirement, 18 years old, eligibility, applicant, negosyo',
            ],

            [
                'agency' => 'Department of Trade and Industry - Occidental Mindoro Provincial Office',

                'question' => 'Does registering my business name with DTI give me a permit to operate?',

                'answer' => 'No. DTI Business Name Registration gives the business name a legal identity, but it does not by itself authorize the business to operate. A separate Business or Mayor\'s Permit and other applicable permits or registrations may still be required.',

                'question_fil' => 'Kapag nag-register ba ako ng business name sa DTI, may permit na akong mag-operate?',

                'answer_fil' => 'Hindi. Ang DTI Business Name Registration ay nagbibigay ng legal identity sa business name pero hindi ito mismo ang authorization para mag-operate ang negosyo. Maaaring kailanganin pa rin ang hiwalay na Business o Mayor\'s Permit at iba pang applicable permits o registrations.',

                'keywords' => 'DTI, business name registration, business permit, Mayor Permit, permit to operate, negosyo, registration',
            ],

            /*
 * =========================================================
 * LANDBANK - SAN JOSE BRANCH
 * =========================================================
 */

[
    'agency' => 'Land Bank of the Philippines - San Jose Branch',

    'question' => 'What do I need to open an individual account with LANDBANK?',

    'answer' => 'For individual account opening, LANDBANK\'s 2026 Citizen\'s Charter lists one valid photo-bearing government-issued ID, preferably showing the applicant\'s complete address. A letter of introduction may also be required when applicable. Additional requirements may depend on the type of account being opened.',

    'question_fil' => 'Ano ang kailangan para magbukas ng individual account sa LANDBANK?',

    'answer_fil' => 'Para sa individual account opening, nakasaad sa 2026 Citizen\'s Charter ng LANDBANK ang isang valid photo-bearing government-issued ID, na mas mainam kung may kumpletong address. Maaari ring kailanganin ang letter of introduction kung applicable. Maaaring magkaroon ng karagdagang requirements depende sa uri ng account na bubuksan.',

    'keywords' => 'LANDBANK, account opening, open account, individual account, requirements, valid ID, government ID',
],

[
    'agency' => 'Land Bank of the Philippines - San Jose Branch',

    'question' => 'Can I open a LANDBANK individual account using one valid government ID?',

    'answer' => 'LANDBANK\'s 2026 Citizen\'s Charter states that an individual account applicant may present one valid photo-bearing government-issued ID, preferably containing the applicant\'s complete address. Other requirements may apply depending on the account type and circumstances.',

    'question_fil' => 'Maaari ba akong magbukas ng LANDBANK individual account gamit ang isang valid government ID?',

    'answer_fil' => 'Ayon sa 2026 Citizen\'s Charter ng LANDBANK, maaaring magpakita ang individual account applicant ng isang valid photo-bearing government-issued ID, na mas mainam kung nakalagay ang kumpletong address. Maaari pa ring magkaroon ng ibang requirements depende sa uri ng account at sitwasyon.',

    'keywords' => 'LANDBANK, one valid ID, government ID, account opening, individual account, requirements',
],


/*
 * =========================================================
 * DEVELOPMENT BANK OF THE PHILIPPINES - SAN JOSE
 * =========================================================
 */

[
    'agency' => 'Development Bank of the Philippines - San Jose Branch',

    'question' => 'What ID do I need to open an individual account with DBP?',

    'answer' => 'DBP\'s Citizen\'s Charter states that individual local residents applying for an account should present at least one original valid photo-bearing identification document. Two pieces of 1x1 identification pictures are also listed among the requirements for the applicable account-opening service.',

    'question_fil' => 'Anong ID ang kailangan para magbukas ng individual account sa DBP?',

    'answer_fil' => 'Ayon sa Citizen\'s Charter ng DBP, ang individual local resident na mag-a-apply para sa account ay kailangang magpakita ng hindi bababa sa isang original at valid na photo-bearing identification document. Nakalista rin ang dalawang piraso ng 1x1 ID picture para sa applicable account-opening service.',

    'keywords' => 'DBP, account opening, individual account, valid ID, government ID, 1x1 picture, requirements',
],

[
    'agency' => 'Development Bank of the Philippines - San Jose Branch',

    'question' => 'Do I need ID pictures when opening an individual account with DBP?',

    'answer' => 'For the applicable individual account-opening service, DBP\'s Citizen\'s Charter lists two pieces of 1x1 identification pictures among the requirements. The applicant must also present at least one original valid photo-bearing identification document.',

    'question_fil' => 'Kailangan ba ng ID pictures kapag magbubukas ng individual account sa DBP?',

    'answer_fil' => 'Para sa applicable individual account-opening service, nakalista sa Citizen\'s Charter ng DBP ang dalawang piraso ng 1x1 ID pictures. Kailangan din ng hindi bababa sa isang original at valid na photo-bearing identification document.',

    'keywords' => 'DBP, ID picture, 1x1, account opening, individual account, requirements, valid ID',
],


/*
 * =========================================================
 * COMMISSION ON ELECTIONS - SAN JOSE OFFICE
 * =========================================================
 */

[
    'agency' => 'Commission on Elections - San Jose Office',

    'question' => 'What are the requirements to register as a voter?',

    'answer' => 'COMELEC states that a voter-registration applicant must be a Filipino citizen, at least 18 years old on or before election day, a resident of the Philippines for at least one year and of the place where the applicant intends to vote for at least six months immediately before the election, and not otherwise disqualified by law. The applicant must also establish identity using an accepted identification document or another identification method allowed by COMELEC.',

    'question_fil' => 'Ano ang requirements para makapagparehistro bilang voter?',

    'answer_fil' => 'Ayon sa COMELEC, kailangang Filipino citizen ang aplikante, hindi bababa sa 18 taong gulang sa o bago ang election day, residente ng Pilipinas nang hindi bababa sa isang taon at residente ng lugar kung saan nais bumoto nang hindi bababa sa anim na buwan bago ang election, at hindi disqualified ng batas. Kailangan ding mapatunayan ang identity gamit ang tinatanggap na identification document o ibang paraan na pinapayagan ng COMELEC.',

    'keywords' => 'COMELEC, voter registration, register voter, requirements, voter, election, Filipino citizen, age, residency',
],

[
    'agency' => 'Commission on Elections - San Jose Office',

    'question' => 'What IDs can I use for voter registration?',

    'answer' => 'COMELEC accepts several identification documents for voter registration, including a National ID, Postal ID, PWD ID, student or library ID signed by the school authority, SC ID, LTO driver\'s license or student permit, NBI clearance, Philippine passport, SSS/GSIS or UMID, IBP ID, PRC license, NCIP certification for applicable ICC/IP applicants, barangay ID or certification with photo, and other valid government-issued IDs.',

    'question_fil' => 'Anong mga ID ang puwedeng gamitin para sa voter registration?',

    'answer_fil' => 'Tumatanggap ang COMELEC ng iba\'t ibang identification document para sa voter registration, kabilang ang National ID, Postal ID, PWD ID, student o library ID na pirmado ng school authority, SC ID, LTO driver\'s license o student permit, NBI clearance, Philippine passport, SSS/GSIS o UMID, IBP ID, PRC license, NCIP certification para sa applicable ICC/IP applicants, barangay ID o certification na may larawan, at iba pang valid government-issued ID.',

    'keywords' => 'COMELEC, voter registration ID, valid ID, National ID, Postal ID, PWD ID, passport, NBI clearance, voter',
],

[
    'agency' => 'Commission on Elections - San Jose Office',

    'question' => 'Can I use a cedula or PNP clearance as my ID for voter registration?',

    'answer' => 'No. COMELEC specifically states that a Community Tax Certificate or cedula and PNP clearance are not accepted as valid identification documents for voter registration.',

    'question_fil' => 'Puwede ba ang cedula o PNP clearance bilang ID para sa voter registration?',

    'answer_fil' => 'Hindi. Malinaw na nakasaad sa COMELEC requirements na hindi tinatanggap ang Community Tax Certificate o cedula at PNP clearance bilang valid identification document para sa voter registration.',

    'keywords' => 'COMELEC, voter registration, cedula, community tax certificate, PNP clearance, valid ID, registration',
],


/*
 * =========================================================
 * DSWD - OCCIDENTAL MINDORO SWAD
 * =========================================================
 */

[
    'agency' => 'Department of Social Welfare and Development - Occidental Mindoro SWAD',

    'question' => 'What documents do I need to apply for DSWD medical assistance for a hospital bill?',

    'answer' => 'For AICS medical assistance for a hospital bill, DSWD Citizen\'s Charter materials list a medical certificate, clinical abstract, discharge summary, or applicable medical document with the physician\'s details; a hospital Statement of Account or applicable billing document; and a valid ID. A Social Case Study Report or Case Summary may also be required depending on the circumstances and amount of assistance requested.',

    'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply ng DSWD medical assistance para sa hospital bill?',

    'answer_fil' => 'Para sa AICS medical assistance para sa hospital bill, kabilang sa mga nakalistang dokumento ng DSWD ang medical certificate, clinical abstract, discharge summary, o applicable medical document na may detalye ng physician; hospital Statement of Account o kaukulang billing document; at valid ID. Maaari ring kailanganin ang Social Case Study Report o Case Summary depende sa sitwasyon at halaga ng assistance na hinihingi.',

    'keywords' => 'DSWD, AICS, medical assistance, hospital bill, requirements, documents, medical certificate, Statement of Account, valid ID',
],

[
    'agency' => 'Department of Social Welfare and Development - Occidental Mindoro SWAD',

    'question' => 'What documents do I need to apply for DSWD educational assistance?',

    'answer' => 'For educational assistance under AICS, DSWD Citizen\'s Charter materials list a validated school ID and a valid ID, together with an enrollment assessment form, certificate of enrollment or registration, or applicable statement of account.',

    'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply ng DSWD educational assistance?',

    'answer_fil' => 'Para sa educational assistance sa ilalim ng AICS, kabilang sa mga nakalistang requirement ng DSWD ang validated school ID at valid ID, kasama ang enrollment assessment form, certificate of enrollment o registration, o applicable statement of account.',

    'keywords' => 'DSWD, AICS, educational assistance, school ID, enrollment, certificate of enrollment, requirements, student assistance',
],

[
    'agency' => 'Department of Social Welfare and Development - Occidental Mindoro SWAD',

    'question' => 'What documents do I need to apply for DSWD burial assistance?',

    'answer' => 'For AICS funeral or burial assistance, DSWD Citizen\'s Charter materials list a death certificate or applicable certification, a promissory note, certificate of balance, or statement of account, and a funeral contract for funeral-bill assistance. Additional documents may apply depending on the specific request.',

    'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply ng DSWD burial assistance?',

    'answer_fil' => 'Para sa AICS funeral o burial assistance, kabilang sa mga nakalistang requirement ng DSWD ang death certificate o applicable certification, promissory note, certificate of balance o statement of account, at funeral contract para sa funeral-bill assistance. Maaari pang magkaroon ng ibang dokumento depende sa partikular na request.',

    'keywords' => 'DSWD, AICS, burial assistance, funeral assistance, death certificate, funeral contract, requirements, documents',
],


/*
 * =========================================================
 * SOCIAL SECURITY SYSTEM - SAN JOSE BRANCH
 * =========================================================
 */

[
    'agency' => 'Social Security System - San Jose Branch',

    'question' => 'How do I get an SSS number for the first time?',

    'answer' => 'SSS requires first-time applicants to apply for an SS Number online or through an SSS electronic center. For online registration, complete the required information, verify the email sent by SSS, complete the remaining registration details, review the information, and generate the SS Number. Supporting documents may also be uploaded when applicable.',

    'question_fil' => 'Paano ako makakakuha ng SSS number kung first time ko pa lang?',

    'answer_fil' => 'Para sa first-time applicants, maaaring mag-apply ng SS Number online o sa SSS electronic center. Sa online registration, kailangang kumpletuhin ang required information, i-verify ang email mula sa SSS, kumpletuhin ang natitirang registration details, i-review ang impormasyon, at i-generate ang SS Number. Maaari ring mag-upload ng supporting documents kung applicable.',

    'keywords' => 'SSS, SS number, SSS registration, first time, register SSS, membership, application, online registration',
],

[
    'agency' => 'Social Security System - San Jose Branch',

    'question' => 'How long is the SSS email link valid after I apply for an SS number?',

    'answer' => 'The SSS online SS Number application email link is valid for five calendar days. If the link expires, the applicant needs to submit a new online application.',

    'question_fil' => 'Gaano katagal valid ang email link ng SSS pagkatapos kong mag-apply ng SS number?',

    'answer_fil' => 'Ang email link para sa SSS online SS Number application ay valid nang limang calendar days. Kapag nag-expire ang link, kailangan magsumite ulit ng bagong online application.',

    'keywords' => 'SSS, SS number, email link, registration, five days, application, expired link',
],

[
    'agency' => 'Social Security System - San Jose Branch',

    'question' => 'Does getting an SSS number automatically make me an active SSS member?',

    'answer' => 'No. SSS states that having an SS Number does not automatically mean that a person is already covered as an SSS member. Coverage depends on the applicable membership category and the corresponding reporting and contribution requirements.',

    'question_fil' => 'Kapag may SSS number na ako, automatic na ba akong active SSS member?',

    'answer_fil' => 'Hindi. Ayon sa SSS, ang pagkakaroon ng SS Number ay hindi awtomatikong nangangahulugan na covered member ka na. Depende ang coverage sa applicable membership category at sa kaukulang reporting at contribution requirements.',

    'keywords' => 'SSS, SS number, active member, coverage, contribution, SSS membership, registration',
],


/*
 * =========================================================
 * CSC - OCCIDENTAL MINDORO FIELD OFFICE
 * =========================================================
 */

[
    'agency' => 'Civil Service Commission - Occidental Mindoro Field Office',

    'question' => 'Can a cum laude graduate apply for Honor Graduate Eligibility under PD 907?',

    'answer' => 'Yes. CSC states that graduates who finished their bachelor\'s degree as Summa Cum Laude, Magna Cum Laude, or Cum Laude may qualify for Honor Graduate Eligibility under Presidential Decree No. 907, provided they meet the applicable conditions and submit the required documents.',

    'question_fil' => 'Maaari bang mag-apply ng Honor Graduate Eligibility sa ilalim ng PD 907 ang cum laude graduate?',

    'answer_fil' => 'Oo. Ayon sa CSC, maaaring maging qualified para sa Honor Graduate Eligibility sa ilalim ng Presidential Decree No. 907 ang mga nagtapos na Summa Cum Laude, Magna Cum Laude, o Cum Laude, basta natutugunan nila ang applicable conditions at naisusumite ang kinakailangang dokumento.',

    'keywords' => 'CSC, Honor Graduate Eligibility, HGE, PD 907, cum laude, magna cum laude, summa cum laude, eligibility',
],

[
    'agency' => 'Civil Service Commission - Occidental Mindoro Field Office',

    'question' => 'What documents do I need to apply for Honor Graduate Eligibility?',

    'answer' => 'CSC lists an accomplished application form, three required ID pictures, a valid identification document, birth certificate, and transcript of records among the requirements for Honor Graduate Eligibility. A certification from the university or college confirming the honor graduate status is also required.',

    'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply ng Honor Graduate Eligibility?',

    'answer_fil' => 'Kabilang sa requirements na nakalista ng CSC para sa Honor Graduate Eligibility ang accomplished application form, tatlong required ID pictures, valid identification document, birth certificate, at transcript of records. Kailangan din ang certification mula sa university o college na nagpapatunay ng honor graduate status.',

    'keywords' => 'CSC, Honor Graduate Eligibility, HGE, requirements, documents, application form, birth certificate, transcript, cum laude',
],

[
    'agency' => 'Civil Service Commission - Occidental Mindoro Field Office',

    'question' => 'Can someone else submit my Honor Graduate Eligibility application for me?',

    'answer' => 'Yes. CSC allows an Honor Graduate Eligibility application to be filed through a representative. The representative must submit an authorization letter from the applicant and an original and photocopy of at least one valid ID of the representative. The applicant must still personally appear for issuance and acceptance of the Certificate of Eligibility if the application is approved.',

    'question_fil' => 'Puwede bang ibang tao ang magsumite ng Honor Graduate Eligibility application ko?',

    'answer_fil' => 'Oo. Pinapayagan ng CSC ang filing ng Honor Graduate Eligibility application sa pamamagitan ng representative. Kailangang magsumite ang representative ng authorization letter mula sa applicant at original at photocopy ng kahit isang valid ID ng representative. Gayunpaman, kailangang personal na humarap ang applicant para sa issuance at acceptance ng Certificate of Eligibility kung maaprubahan ang application.',

    'keywords' => 'CSC, HGE, Honor Graduate Eligibility, representative, authorization letter, valid ID, Certificate of Eligibility',
],


/*
 * =========================================================
 * NATIONAL BUREAU OF INVESTIGATION - SAN JOSE
 * =========================================================
 */

[
    'agency' => 'National Bureau of Investigation - San Jose Satellite Office',

    'question' => 'How do I apply for an NBI Clearance?',

    'answer' => 'For an NBI Clearance application, start through the official NBI Clearance online system. Register or log in, complete the Applicant Information Form, select the required valid government ID, choose a branch and appointment schedule, and complete the payment process. After the transaction is paid, proceed to the selected branch for biometric and image capture and clearance processing.',

    'question_fil' => 'Paano ako mag-a-apply ng NBI Clearance?',

    'answer_fil' => 'Para sa NBI Clearance application, magsimula sa official NBI Clearance online system. Mag-register o mag-log in, kumpletuhin ang Applicant Information Form, piliin ang kinakailangang valid government ID, pumili ng branch at appointment schedule, at kumpletuhin ang payment process. Kapag paid na ang transaction, pumunta sa napiling branch para sa biometric at image capture at clearance processing.',

    'keywords' => 'NBI, NBI Clearance, apply NBI, clearance application, online application, appointment, requirements, biometrics',
],

[
    'agency' => 'National Bureau of Investigation - San Jose Satellite Office',

    'question' => 'How many valid IDs do I need for an NBI Clearance application?',

    'answer' => 'The NBI Citizen\'s Charter for clearance application lists two valid government-issued identification cards as a requirement. Applicants should bring the original valid IDs required by the current NBI application process.',

    'question_fil' => 'Ilang valid ID ang kailangan para sa NBI Clearance application?',

    'answer_fil' => 'Dalawang valid government-issued identification cards ang nakalista bilang requirement sa NBI Citizen\'s Charter para sa clearance application. Dapat dalhin ng applicant ang kinakailangang original at valid IDs ayon sa kasalukuyang NBI application process.',

    'keywords' => 'NBI, NBI Clearance, valid IDs, two IDs, requirements, government ID, clearance application',
],

[
    'agency' => 'National Bureau of Investigation - San Jose Satellite Office',

    'question' => 'Can I get an NBI Clearance for free if I am a first-time job seeker?',

    'answer' => 'Yes. The NBI Citizen\'s Charter provides a First-Time Job Seeker process under Republic Act No. 11261. Qualified first-time job seekers must present the required Barangay Certification and two valid government-issued IDs or acceptable certificates. The NBI clearance is free for qualified first-time job seekers.',

    'question_fil' => 'Libre ba ang NBI Clearance kung first-time job seeker ako?',

    'answer_fil' => 'Oo. May First-Time Job Seeker process ang NBI sa ilalim ng Republic Act No. 11261. Kailangang magpakita ng kinakailangang Barangay Certification at dalawang valid government-issued IDs o acceptable certificates. Libre ang NBI clearance para sa qualified first-time job seekers.',

    'keywords' => 'NBI, first time job seeker, FTJS, free NBI clearance, RA 11261, barangay certification, valid ID',
],

[
    'agency' => 'Regional Trial Court - San Jose, Occidental Mindoro',

    'question' => 'What documents should I prepare if I need to file a complaint for a case that requires preliminary investigation?',

    'answer' => 'For a criminal complaint requiring preliminary investigation, the complaint should be accompanied by the complainant\'s affidavit, affidavits of witnesses, and supporting documents establishing the complaint. The required number of copies depends on the number of respondents and the official file requirements. Confirm the filing requirements with the proper prosecutor or court office before submission.',

    'question_fil' => 'Anong mga dokumento ang dapat kong ihanda kung kailangan kong magsampa ng reklamo na nangangailangan ng preliminary investigation?',

    'answer_fil' => 'Para sa criminal complaint na nangangailangan ng preliminary investigation, dapat may kasamang affidavit ng complainant, affidavits ng mga witness, at supporting documents na makakatulong sa reklamo. Ang bilang ng copies ay depende sa dami ng respondents at sa requirements para sa official file. Kumpirmahin muna ang filing requirements sa tamang prosecutor o court office bago magsumite.',

    'keywords' => 'RTC, Regional Trial Court, criminal complaint, preliminary investigation, complaint affidavit, witness affidavit, supporting documents, filing',
],

[
    'agency' => 'Regional Trial Court - San Jose, Occidental Mindoro',

    'question' => 'What should I do if I receive a subpoena for a preliminary investigation?',

    'answer' => 'If you receive a subpoena for a preliminary investigation, review the complaint and supporting documents attached to it and prepare your counter-affidavit and supporting evidence. Under Rule 112, a respondent generally has ten days from receipt of the subpoena and accompanying documents to submit the counter-affidavit and supporting documents. If you are unsure how to respond, consider seeking qualified legal assistance.',

    'question_fil' => 'Ano ang dapat kong gawin kung nakatanggap ako ng subpoena para sa preliminary investigation?',

    'answer_fil' => 'Kung nakatanggap ka ng subpoena para sa preliminary investigation, basahin ang complaint at mga supporting documents na kasama nito at ihanda ang iyong counter-affidavit at supporting evidence. Sa ilalim ng Rule 112, karaniwang may sampung araw mula sa pagtanggap ng subpoena at mga dokumento para magsumite ng counter-affidavit at supporting documents. Kung hindi ka sigurado kung paano sasagot, maaari kang humingi ng tulong sa isang kwalipikadong legal professional.',

    'keywords' => 'RTC, subpoena, preliminary investigation, respondent, counter-affidavit, ten days, legal assistance',
],

[
    'agency' => 'Public Attorney\'s Office - San Jose District Office',

    'question' => 'What documents can I use to prove that I am indigent when applying for PAO legal assistance?',

    'answer' => 'PAO may require proof of indigency when determining eligibility for free legal assistance. Acceptable proof may include a latest Income Tax Return or other proof of net income, a Certificate of Indigency from DSWD or the local social welfare office, or a Certificate of Indigency or No Income from the barangay. An Affidavit of Indigency may also be required.',

    'question_fil' => 'Anong mga dokumento ang maaaring gamitin para mapatunayan na indigent ako kapag nag-a-apply ng legal assistance sa PAO?',

    'answer_fil' => 'Maaaring mangailangan ang PAO ng proof of indigency para malaman kung kwalipikado ka sa libreng legal assistance. Maaaring kabilang dito ang latest Income Tax Return o ibang proof of net income, Certificate of Indigency mula sa DSWD o local social welfare office, o Certificate of Indigency o No Income mula sa barangay. Maaari ring kailanganin ang Affidavit of Indigency.',

    'keywords' => 'PAO, Public Attorney Office, legal assistance, free legal assistance, indigency, certificate of indigency, affidavit of indigency',
],

[
    'agency' => 'Public Attorney\'s Office - San Jose District Office',

    'question' => 'What kinds of cases can PAO help me with for free?',

    'answer' => 'PAO provides free legal representation, assistance, and counseling to qualified indigent persons in criminal, civil, labor, administrative, and other quasi-judicial cases. Eligibility and the circumstances of the case are still evaluated under PAO rules.',

    'question_fil' => 'Anong mga kaso ang maaaring tulungan ng PAO nang libre?',

    'answer_fil' => 'Nagbibigay ang PAO ng libreng legal representation, assistance, at counseling sa mga kwalipikadong indigent persons para sa criminal, civil, labor, administrative, at iba pang quasi-judicial cases. Sinusuri pa rin ng PAO kung kwalipikado ang aplikante at kung pasok ang kaso sa kanilang mga patakaran.',

    'keywords' => 'PAO, free legal assistance, legal representation, criminal case, civil case, labor case, administrative case',
],

[
    'agency' => 'Bureau of Jail Management and Penology - San Jose District Jail',

    'question' => 'What do I need to bring if I am visiting a person detained at the jail for the first time?',

    'answer' => 'New jail visitors must present the required identification or relationship documents before visitor registration. The specific documents depend on the visitor\'s relationship with the person deprived of liberty. BJMP visitor registration also includes completing the visitor form and biometric registration before the visitation process.',

    'question_fil' => 'Ano ang kailangan kong dalhin kung bibisita ako sa taong nakakulong sa jail sa unang pagkakataon?',

    'answer_fil' => 'Ang bagong jail visitor ay kailangang magpakita ng kinakailangang identification o relationship documents bago ang visitor registration. Depende sa relasyon sa person deprived of liberty ang eksaktong dokumentong kailangan. Kasama rin sa proseso ang pagsagot sa visitor form at biometric registration bago ang visitation.',

    'keywords' => 'BJMP, jail visit, visitation, visitor registration, first time visitor, PDL, valid ID, requirements',
],

[
    'agency' => 'Bureau of Jail Management and Penology - San Jose District Jail',

    'question' => 'Do I need to register before I can visit a person in jail?',

    'answer' => 'Yes. BJMP\'s visitor process requires new visitors to undergo registration before visitation. The registration includes checking the visitor\'s requirements, recording visitor information, and biometric registration. Visitors then follow the jail\'s queuing and visitation procedures.',

    'question_fil' => 'Kailangan ba munang magparehistro bago ako makabisita sa isang taong nakakulong?',

    'answer_fil' => 'Oo. Kailangang dumaan sa registration ang mga bagong visitor bago makabisita. Kasama sa registration ang pag-check ng requirements, pag-record ng visitor information, at biometric registration. Pagkatapos nito, susundin ng visitor ang queuing at visitation procedures ng jail.',

    'keywords' => 'BJMP, jail visitation, visitor registration, biometric registration, queue, visitation procedure',
],

[
    'agency' => 'Department of Justice - Occidental Mindoro',

    'question' => 'What documents do I need to file a complaint for preliminary investigation?',

    'answer' => 'For a complaint directly filed by a private individual or entity, the DOJ checklist includes the accomplished Investigation Data Form, a complaint-affidavit or sworn statement of the complainant or victim, affidavits or sworn statements of witnesses, and supporting documents when required. Additional documents may apply depending on the type of complaint.',

    'question_fil' => 'Anong mga dokumento ang kailangan para magsampa ng complaint para sa preliminary investigation?',

    'answer_fil' => 'Para sa complaint na direktang inihahain ng private individual o entity, kasama sa DOJ checklist ang accomplished Investigation Data Form, complaint-affidavit o sworn statement ng complainant o victim, affidavits o sworn statements ng witnesses, at supporting documents kapag kinakailangan. Maaaring may dagdag na dokumento depende sa uri ng complaint.',

    'keywords' => 'DOJ, Department of Justice, preliminary investigation, complaint, complaint affidavit, investigation data form, witness affidavit',
],

[
    'agency' => 'Department of Justice - Occidental Mindoro',

    'question' => 'What happens after a respondent receives a subpoena for preliminary investigation?',

    'answer' => 'After receiving the subpoena and the complaint documents, the respondent may examine the evidence submitted by the complainant and should submit a counter-affidavit, witness affidavits, and supporting documents within the period stated in the applicable procedure. Under Rule 112, the usual period is ten days from receipt of the subpoena and accompanying documents.',

    'question_fil' => 'Ano ang dapat gawin pagkatapos makatanggap ng subpoena para sa preliminary investigation?',

    'answer_fil' => 'Pagkatapos matanggap ang subpoena at complaint documents, maaaring suriin ng respondent ang evidence na isinumite ng complainant at dapat magsumite ng counter-affidavit, witness affidavits, at supporting documents sa loob ng itinakdang panahon. Sa ilalim ng Rule 112, karaniwang sampung araw mula sa pagtanggap ng subpoena at mga dokumento ang panahon para magsumite.',

    'keywords' => 'DOJ, subpoena, preliminary investigation, respondent, counter-affidavit, witness affidavit, ten days',
],

[
    'agency' => 'Philippine Charity Sweepstakes Office - Occidental Mindoro Branch',

    'question' => 'What documents do I need to apply for PCSO medical assistance for a hospital bill?',

    'answer' => 'For PCSO Medical Assistance Program assistance involving confinement, the current Citizen\'s Charter lists an accomplished MAP Application Form, medical abstract, Statement of Account or applicable discharge document, a copy of the Guarantee Letter or claim slip when applicable, and a photocopy of the patient\'s or representative\'s valid ID.',

    'question_fil' => 'Anong mga dokumento ang kailangan para mag-apply ng PCSO medical assistance para sa hospital bill?',

    'answer_fil' => 'Para sa PCSO Medical Assistance Program na may kaugnayan sa confinement, kabilang sa kasalukuyang Citizen\'s Charter ang accomplished MAP Application Form, medical abstract, Statement of Account o applicable discharge document, kopya ng Guarantee Letter o claim slip kapag naaangkop, at photocopy ng valid ID ng patient o representative.',

    'keywords' => 'PCSO, medical assistance, MAP, hospital bill, confinement, medical abstract, statement of account, guarantee letter',
],

[
    'agency' => 'Philippine Charity Sweepstakes Office - Occidental Mindoro Branch',

    'question' => 'What documents are needed for PCSO assistance for dialysis or specific medicines?',

    'answer' => 'For dialysis assistance, PCSO lists the MAP Application Form, medical abstract, charge slips, Statement of Account, physician-signed prescription, copy of the Guarantee Letter, and patient ID among the requirements. Requirements for specific medicines include medical documentation, prescription, sales invoice or receipt, Statement of Account, Guarantee Letter, and patient ID, depending on the request.',

    'question_fil' => 'Anong mga dokumento ang kailangan para sa PCSO assistance para sa dialysis o gamot?',

    'answer_fil' => 'Para sa dialysis assistance, kabilang sa requirements ng PCSO ang MAP Application Form, medical abstract, charge slips, Statement of Account, prescription na pirmado ng doktor, kopya ng Guarantee Letter, at patient ID. Para naman sa specific medicines, maaaring kailanganin ang medical documents, prescription, sales invoice o receipt, Statement of Account, Guarantee Letter, at patient ID depende sa request.',

    'keywords' => 'PCSO, MAP, dialysis assistance, medicine assistance, prescription, charge slip, guarantee letter, medical assistance',
],

[
    'agency' => 'Department of Environment and Natural Resources - CENRO San Jose',

    'question' => 'What do I need to prepare if I want to apply for a permit to cut naturally grown trees on private land?',

    'answer' => 'For a Private Land Timber Permit, DENR lists requirements such as a letter application from the landowner, an authenticated copy of the land title or CLOA with an approved sketch map, applicable development plans, required LGU endorsements, inventory documents, and a CENRO certification that the land is within certified alienable and disposable land. Additional requirements may apply depending on the property and application.',

    'question_fil' => 'Ano ang kailangan kong ihanda kung gusto kong mag-apply ng permit para magputol ng naturally grown trees sa private land?',

    'answer_fil' => 'Para sa Private Land Timber Permit, kabilang sa requirements ng DENR ang letter application ng landowner, authenticated copy ng land title o CLOA na may approved sketch map, applicable development plans, kinakailangang LGU endorsements, inventory documents, at CENRO certification na ang lupa ay nasa certified alienable and disposable land. Maaaring may dagdag na requirements depende sa property at application.',

    'keywords' => 'DENR, CENRO, tree cutting permit, private land, naturally grown trees, timber permit, land title, CLOA',
],

[
    'agency' => 'Department of Environment and Natural Resources - CENRO San Jose',

    'question' => 'Do I always need a tree-cutting permit for trees on a residential lot?',

    'answer' => 'Not always. DENR Administrative Order No. 2026-06 provides a permit exemption for cutting, gathering, collecting, or removing not more than two trees within a residential lot, subject to the required prior notice to the concerned DENR office. Trees or tree derivatives transported outside the residential lot may still require the appropriate transport document.',

    'question_fil' => 'Kailangan ba palaging may tree-cutting permit para sa mga puno sa residential lot?',

    'answer_fil' => 'Hindi palagi. Ayon sa DENR Administrative Order No. 2026-06, may permit exemption para sa pagputol, pangongolekta, o pagtanggal ng hindi hihigit sa dalawang puno sa loob ng residential lot, basta masunod ang kinakailangang prior notice sa concerned DENR office. Maaari pa ring kailanganin ang tamang transport document kung ilalabas sa residential lot ang logs o tree derivatives.',

    'keywords' => 'DENR, CENRO, tree cutting, residential lot, two trees, permit exemption, DAO 2026-06, transport document',
],

[
    'agency' => 'Cooperative Development Authority - Occidental Mindoro',

    'question' => 'How many people are needed to organize a primary cooperative?',

    'answer' => 'A primary cooperative may be organized by at least fifteen natural persons who are Filipino citizens, of legal age, have a common bond of interest, and actually reside or work in the intended area of operation. Prospective members must also comply with the applicable pre-membership education requirements.',

    'question_fil' => 'Ilang tao ang kailangan para makapag-organisa ng primary cooperative?',

    'answer_fil' => 'Maaaring mag-organisa ng primary cooperative ang hindi bababa sa labinlimang natural persons na Filipino citizens, nasa legal age, may common bond of interest, at aktuwal na nakatira o nagtatrabaho sa intended area of operation. Kailangan ding matugunan ng prospective members ang applicable pre-membership education requirements.',

    'keywords' => 'CDA, cooperative, primary cooperative, fifteen members, registration, members, PMES',
],

[
    'agency' => 'Cooperative Development Authority - Occidental Mindoro',

    'question' => 'What documents are required to register a cooperative with CDA?',

    'answer' => 'CDA registration requirements include documents such as the Economic Survey, notarized Articles of Cooperation, By-laws, Treasurer\'s Affidavit, Surety Bonds of accountable officers, Certificate of Pre-Registration Seminar, Business Plan, and favorable endorsements from other governing agencies when applicable. The exact requirements depend on the type of cooperative being registered.',

    'question_fil' => 'Anong mga dokumento ang kailangan para i-register ang cooperative sa CDA?',

    'answer_fil' => 'Kabilang sa CDA registration requirements ang Economic Survey, notarized Articles of Cooperation, By-laws, Treasurer\'s Affidavit, Surety Bonds ng accountable officers, Certificate of Pre-Registration Seminar, Business Plan, at favorable endorsements mula sa ibang governing agencies kapag applicable. Ang eksaktong requirements ay depende sa uri ng cooperative na ipaparehistro.',

    'keywords' => 'CDA, cooperative registration, Economic Survey, Articles of Cooperation, bylaws, Treasurer Affidavit, Business Plan, registration requirements',
],

[
    'agency' => 'National Commission on Indigenous Peoples - Occidental Mindoro',

    'question' => 'What is the Certificate of Confirmation used for when applying for benefits as an Indigenous Person?',

    'answer' => 'The NCIP Certificate of Confirmation confirms an individual\'s Indigenous Cultural Community or Indigenous Peoples Membership certification. NCIP states that the Certificate of Confirmation is the official document used to avail of certain benefits and privileges for ICCs/IPs in areas such as education, employment, and basic social services.',

    'question_fil' => 'Para saan ginagamit ang Certificate of Confirmation kapag nag-a-apply ng benefits bilang Indigenous Person?',

    'answer_fil' => 'Ang Certificate of Confirmation ng NCIP ay nagpapatunay sa Indigenous Cultural Community o Indigenous Peoples Membership certification ng isang tao. Ayon sa NCIP, ito ang official document na ginagamit para sa ilang benefits at privileges para sa ICCs/IPs tulad ng education, employment, at basic social services.',

    'keywords' => 'NCIP, Certificate of Confirmation, COC, Indigenous Peoples, IP membership, ICC, benefits, privileges',
],

[
    'agency' => 'National Commission on Indigenous Peoples - Occidental Mindoro',

    'question' => 'Can someone who is not an Indigenous Person get a Certificate of Confirmation by being adopted into a tribe?',

    'answer' => 'No. NCIP states that tribal adoption for the purpose of obtaining a Certificate of Confirmation to receive benefits and privileges for ICCs/IPs is not recognized under its applicable rules. People should also avoid fixers or individuals offering to obtain certificates through improper means.',

    'question_fil' => 'Maaari bang makakuha ng Certificate of Confirmation ang hindi Indigenous Person sa pamamagitan ng pag-adopt sa isang tribe?',

    'answer_fil' => 'Hindi. Ayon sa NCIP, hindi kinikilala ang tribal adoption para lamang makakuha ng Certificate of Confirmation at makagamit ng benefits at privileges para sa ICCs/IPs. Dapat ding iwasan ang fixers o mga taong nag-aalok na kumuha ng certificate sa hindi tamang paraan.',

    'keywords' => 'NCIP, Certificate of Confirmation, tribal adoption, Indigenous Person, IP, ICC, fixer, certification',
],

[
    'agency' => 'Bureau of Internal Revenue - Revenue District Office No. 37',

    'question' => 'Can I apply for a TIN online through BIR?',

    'answer' => 'Yes. BIR provides online TIN registration through the Online Registration and Update System (ORUS) for several taxpayer-registration transactions. The appropriate ORUS registration process depends on whether you are registering as an employee, an individual with a one-time transaction, or an individual new business registrant.',

    'question_fil' => 'Maaari ba akong mag-apply ng TIN online sa BIR?',

    'answer_fil' => 'Oo. May online TIN registration ang BIR gamit ang Online Registration and Update System o ORUS para sa ilang taxpayer-registration transactions. Depende sa iyong situation ang tamang ORUS process, gaya ng employee registration, one-time transaction, o individual new business registration.',

    'keywords' => 'BIR, TIN, taxpayer identification number, ORUS, online registration, employee TIN, business TIN',
],

[
    'agency' => 'Bureau of Internal Revenue - Revenue District Office No. 37',

    'question' => 'Where do I register for a TIN if I am starting a new individual business?',

    'answer' => 'BIR provides an online ORUS process for individual new business registrants and also provides a manual walk-in process through the appropriate Revenue District Office. The correct registration office depends on the taxpayer and business circumstances, so confirm the applicable RDO and current documentary requirements before filing.',

    'question_fil' => 'Saan ako magpaparehistro para sa TIN kung magsisimula ako ng individual business?',

    'answer_fil' => 'May online ORUS process ang BIR para sa individual new business registrants at mayroon ding manual walk-in process sa appropriate Revenue District Office. Depende sa taxpayer at business circumstances ang tamang registration office, kaya mabuting kumpirmahin muna ang applicable RDO at kasalukuyang documentary requirements bago mag-file.',

    'keywords' => 'BIR, TIN, new business, individual business, ORUS, RDO, Revenue District Office, business registration',
],

[
    'agency' => 'Department of Labor and Employment - Occidental Mindoro Provincial Office',

    'question' => 'Can I file a DOLE request for assistance if I have a problem with my employer?',

    'answer' => 'Yes. DOLE\'s Single Entry Approach or SEnA allows an aggrieved worker, group of workers, union, or employer to file a Request for Assistance for covered labor and employment issues. SEnA is intended to provide a speedy, impartial, inexpensive, and accessible conciliation-mediation process.',

    'question_fil' => 'Maaari ba akong magsumite ng request for assistance sa DOLE kung may problema ako sa employer ko?',

    'answer_fil' => 'Oo. Sa pamamagitan ng Single Entry Approach o SEnA ng DOLE, maaaring magsumite ng Request for Assistance ang aggrieved worker, group of workers, union, o employer para sa mga covered labor and employment issues. Layunin ng SEnA na magkaroon ng mabilis, patas, mura, at accessible na conciliation-mediation process.',

    'keywords' => 'DOLE, SEnA, Request for Assistance, labor complaint, employer, employee, labor dispute, conciliation',
],

[
    'agency' => 'Department of Labor and Employment - Occidental Mindoro Provincial Office',

    'question' => 'How long does the DOLE SEnA conciliation process normally take?',

    'answer' => 'DOLE states that SEnA uses a 30-calendar-day conciliation-mediation period. If the parties do not reach a settlement within the applicable period, the case may be elevated to the appropriate office or process under the applicable rules.',

    'question_fil' => 'Gaano katagal karaniwang tumatagal ang DOLE SEnA conciliation process?',

    'answer_fil' => 'Ayon sa DOLE, ang SEnA ay gumagamit ng 30-calendar-day conciliation-mediation period. Kung walang settlement sa loob ng applicable period, maaaring i-elevate ang matter sa appropriate office o proseso ayon sa applicable rules.',

    'keywords' => 'DOLE, SEnA, 30 days, conciliation mediation, labor dispute, Request for Assistance, settlement',
],
        ];

        /*
         * Collect every unique agency name used by the dataset.
         */
        $agencyNames = collect($faqs)
            ->pluck('agency')
            ->unique()
            ->values();

        /*
         * Resolve the actual database IDs in one query.
         */
        $agencyIds = Agency::whereIn(
            'agency_name',
            $agencyNames
        )->pluck('id', 'agency_name');

        /*
         * Stop the seeder if any referenced agency does not exist.
         */
        if ($agencyIds->count() !== $agencyNames->count()) {

            $missingAgencies = $agencyNames
                ->reject(
                    fn ($agencyName) =>
                        $agencyIds->has($agencyName)
                )
                ->implode(', ');

            throw new RuntimeException(
                "The following FAQ agencies were not found: {$missingAgencies}"
            );
        }

        /*
         * Counters for the Artisan output.
         */
        $inserted = 0;
        $skipped = 0;

        /*
         * Insert the complete dataset inside one database transaction.
         */
        DB::transaction(function () use (
            $faqs,
            $agencyIds,
            &$inserted,
            &$skipped
        ) {

            /*
             * Process every FAQ record.
             */
            foreach ($faqs as $data) {

                /*
                 * Convert the readable agency name into its
                 * corresponding foreign-key ID.
                 */
                $agencyId = $agencyIds->get(
                    $data['agency']
                );

                /*
                 * Check active and soft-deleted records so
                 * rerunning the seeder does not create duplicates.
                 */
                $existingFaq = Faq::withTrashed()
                    ->where('agency_id', $agencyId)
                    ->where('question', $data['question'])
                    ->first();

                /*
                 * Keep an existing record untouched.
                 */
                if ($existingFaq) {
                    $skipped++;

                    continue;
                }

                /*
                 * Create the FAQ using the fields supported
                 * by the Faq model and database schema.
                 */
                Faq::create([
                    'agency_id' => $agencyId,
                    'question' => $data['question'],
                    'answer' => $data['answer'],
                    'question_fil' => $data['question_fil'],
                    'answer_fil' => $data['answer_fil'],
                    'keywords' => $data['keywords'],
                    'image' => null,
                ]);

                /*
                 * Count the successful insertion.
                 */
                $inserted++;
            }
        });

        /*
         * Display the final result in the Artisan console.
         */
        $this->command->info(
            'Temporary FAQ seeder completed.'
        );

        $this->command->info(
            "Inserted: {$inserted}"
        );

        $this->command->info(
            "Skipped existing: {$skipped}"
        );
    }
}