<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyContact;
use App\Models\AgencyType;
use App\Models\Category;
use App\Models\ContactType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporaryNgoSeeder extends Seeder
{
    /**
     * Temporary, standalone dataset of accredited civil society organizations.
     *
     * Source:
     * "Accredited CSO 2025-2028", Office of the Sangguniang Bayan,
     * Municipality of San Jose, Province of Occidental Mindoro.
     * The source document states "As of June 10, 2026".
     *
     * All 177 rows are transcribed from the official accreditation list.
     * The accreditation number is used as the stable organization code.
     * No unverified coordinates, office hours, websites, or services are invented.
     *
     * This seeder is intentionally NOT registered in DatabaseSeeder.
     * Run it explicitly with:
     * php artisan db:seed --class=TemporaryNgoSeeder
     */
    public function run(): void
    {
        /*
         * Each row follows the exact source columns:
         * accreditation number, organization name, chairman, address, contact no.
         * Service-sector categories are mapped separately by accreditation number.
         */
        $organizations = [
            ['2025-001SB', 'Phenomenal Management of Ambulong Water Service Cooperative', 'Mr. Domingo “Dondon” S. Soriao, Jr.', 'Ambulong, San Jose, Occ. Mindoro', '0999-798-8089'],
            ['2025-002SB', 'United Farmers Development Association (UFDA)', 'Mr. Ryan Noel V. Cadiao', 'Bubog, San Jose, Occ. Mindoro', '0997-560-8503'],
            ['2025-003SB', 'Mapaya Salt Producers Association', 'Ms. Leonida L. Gloria', 'Sitio Cusol, Mapaya 3, San Jose, Occ. Mindoro', '0939-171-9470'],
            ['2025-004SB', 'Samahan ng Kababaihan ng Sitio Tamaraw', 'Ms. Laidycare C. Aguilar', 'Sitio Tamaraw, La Curva, San Jose, Occ. Mindoro', '0950-039-7120'],
            ['2025-005SB', 'Bayotbot Elderly Association', 'Ms. Luzviminda P. Obaña', 'Bayotbot, San Jose, Occ. Mindoro', '0970-199-7879'],
            ['2025-006SB', 'One Mindoro Marketing Association Incorporated', 'Mr. Ernan E. Declito', 'Patterson, Bagong Sikat, San Jose Occ. Mindoro', '0910-942-4136'],
            ['2025-007SB', 'Market Vendors Alliance Credit Cooperative', 'Ms. Avelina M. Colonia', 'Public Market, Poblacion 4, San Jose, Occ. Mindoro', '0929-361-4665'],
            ['2025-008SB', 'Mapaya Salt and Fish Farm Association', 'Ms. Maxima V. Estores', 'Sitio Cusol, Mapaya 3, San Jose, Occ. Mindoro', '0916-257-5851'],
            ['2025-009SB', 'Antipolo Rice and Vegetable Farmers Association (ARVEFA)', 'Ms. Bella B. Erodias', 'Sitio Antipolo, Mangarin, San Jose, Occidental Mindoro', '0948-696-6560'],
            ['2025-010SB', 'Bubog Purok Siete Association', 'Mr. Santiago V. Isla', 'Purok 7, Bubog, San Jose, Occidental Mindoro', '0948-415-7787'],
            ['2025-011SB', 'Samahang Magsasaka ng Palanghiran at Imbarasan', 'Mr. Ricardo E. dela Cruz', 'Sitio Imbarasan, Mapaya, San Jose, Occidental Mindoro', '0998-984-2896'],
            ['2025-012SB', 'Gomez Village Vendors Association', 'Ms. Marilyn B. Venus', 'Gomez Village, Pag-asa, San Jose, Occidental Mindoro', '0981-751-7125'],
            ['2025-013SB', 'Lambaklad Fishermen Cooperative', 'Mr. Hernani B. Ocate', 'Sitio Lamis, San Agustin, San Jose, Occidental Mindoro', '0961-269-4429'],
            ['2025-014SB', 'Kaagapay ng San Agustin Magsasaka Mangingisda Association', 'Mr. Renato R. Oquindo', 'San Agustin Main, San Jose, Occidental Mindoro', '0906-570-8425'],
            ['2025-015SB', 'Makabuhay Rotary Village Corp Multi-Purpose Cooperative', 'Mr. Jasper G. Mallar', 'Millsite 2, Central, San Jose, Occidental Mindoro', '0917-649-4627'],
            ['2025-016SB', 'Tamaraw Salt Producers Cooperative of Occidental Mindoro (TAMACO)', 'Mr. Felix Bernales, Jr.', '0690 Burgos St., Pag-asa, San Jose, Occidental Mindoro', '0956-621-7735'],
            ['2025-017SB', 'Eskwela Tricycle Operators and Drivers Association (ETODA)', 'Mr. Michael E. Delos Santos', 'Ducalan 2, Labangan Poblacion, San Jose, Occidental Mindoro', '0966-819-6514'],
            ['2025-018SB', 'Boundary Hospital Tricycle Operator and Drivers Association (BHTODA)', 'Mr. Ronaldo Guingon', 'San Isidro, San Jose, Occidental Mindoro', '0926-511-4372'],
            ['2025-019SB', 'Caminawit Pag-asa Boundary (CPBTODA)', 'Mr. Emmanuel B. Agnais', 'Tagumpay II, Caminawit, San Jose, Occidental Mindoro', '0970-707-3275'],
            ['2025-020SB', 'San Jose Central Busuanga Boundary TODA', 'Mr. Edwin V. Olarte', 'Central, San Jose, Occidental Mindoro', '0909-619-0962'],
            ['2025-021SB', 'Mindoro Occidental Tricycle Operator and Drivers Association (MOTODA)', 'Mr. Elmer B. Dela Cruz', 'Caminawit, San Jose, Occidental Mindoro', '0930-356-5159'],
            ['2025-022SB', 'Bagong Sikat Tricycle Operators and Drivers Association (BSTODA)', 'Mr. Clemente U. Bautista', 'Bagong Sikat, San Jose, Occidental Mindoro', '0945-294-9848'],
            ['2025-023SB', 'Mapaya II Barombong Tagumpay Tricycle Operator sand Drivers Association, Inc', 'Mr. Jogie Mark T. Dela Cruz', 'Barombong, Mapaya, San Jose, Occidental Mindoro', '0967-816-6250'],
            ['2025-024SB', 'San Roque I Tricycle Operators and Drivers Association', 'Mr. Enrique De Lara', 'San Roque I, San Jose, Occidental Mindoro', '0948-900-1267'],
            ['2025-025SB', 'Bubog TODA', 'Mr. Jimmy L. Gomez', 'Bubog, San Jose, Occidental Mindoro', '0948-545-6202'],
            ['2025-026SB', 'Tricycle Mapaya Operators and Drivers Association (TRIMAPTODA)', 'Mr. Dennis V. Baticados', 'Mapaya III, San Jose, Occidental Mindoro', '0992-553-3653'],
            ['2025-027SB', 'Bagong Sikat Mendiola Medalla Tricycle Operator and Drivers Association (BSMMTODA)', 'Mr. Jerome B. Nacario', 'Bagong Sikat, San Jose, Occidental Mindoro', '0910-628-1700'],
            ['2025-028SB', 'San Jose National High School Tricycle Operators Driver Association', 'Mr. Jose Marineo L. Barbosa', 'Bagong Sikat, San Jose, Occidental Mindoro', '0966-492-1985'],
            ['2025-029SB', 'SPW TODA', 'Mr. Reymart M. Dela Cruz', 'Purok 2A White House, San Roque I, San Jose, Occidental Mindoro', '0926-399-9176'],
            ['2025-030SB', 'Tamaraw Development Cooperative', 'Mr. Reynaldo R. Yalong', 'La Curva, San Jose, Occidental Mindoro', '0946-557-7098'],
            ['2025-031SB', 'Kaagapay Kilusang Bayang Tagapagpaunlad Multipurpose Cooperative (KAAKIBAT MPC)', 'Mr. Ramon M. Silverio', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0950-642-0584 / 0916-877-8559'],
            ['2025-032SB', 'Samahan ng mga Mangingisda at Mamumusit ng San Roque I', 'Mr. Oliver M. Galabay', 'San Roque I, San Jose, Occidental Mindoro', '0965-114-3438'],
            ['2025-033SB', 'Council for the Restoration of Filipino Values, Inc.', 'Ptr. Bryan D. Eniego', 'Marsan Village, San Roque, San Jose, Occidental Mindoro', '0919-079-0794'],
            ['2025-034SB', 'Samahan ng mga Kababaihan ng Purok 6 Barangay Pag-asa', 'Ms. Melanie M. Mariano', 'Gomez Village, Pag-asa, San Jose, Occidental Mindoro', '0910-983-5721'],
            ['2025-035SB', 'Watchers Multi-Purpose Cooperative', 'Mr. Helbert S. Cortes', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0912-608-0503'],
            ['2025-036SB', 'Molases Farmer and Fishery Association', 'Ms. Aurora Erison', 'Molases, Bubog, San Jose, Occidental Mindoro', '0946-257-9314'],
            ['2025-037SB', 'Patterson Workers Association', 'Ms. Juliet V. Jose', 'Purok Pag-asa B, Bagong Sikat, San Jose, Occidental Mindoro', '0905-840-0803'],
            ['2025-038SB', 'Occidental Mindoro Cooperative Bank', 'Mr. Ramon M. Silverio', 'Lapu-Lapu St., Poblacion 5, San Jose, Occidental Mindoro', '(043)458-1904'],
            ['2025-039SB', 'San Agustin Fisherfolk Manin Barrio Association (SAFMA)', 'Mr. Roberto P. Oquendo', 'Main Barrio, San Agustin, San Jose, Occidental Mindoro', '0977-295-8994'],
            ['2025-040SB', 'Ka-Barbers Association of San Jose (KABARASSAN)', 'Mr. Christian Colendres', 'Rizal St., Poblacion 6, San Jose, Occidental Mindoro', '0930-372-3368'],
            ['2025-041SB', 'Retired Employees Association', 'Ms. Josefina S. Villamar', 'Pag-asa, San Jose, Occidental Mindoro', '0912-321-5423'],
            ['2025-042SB', 'Isang Barangay Pagkakaisa Association', 'Ms. Mylene F. Garcia', 'Purok 3, Magbay, San Jose, Occidental Mindoro', '0919-824-8732'],
            ['2025-043SB', 'Mga Nanay Tamaraw Chapter (MNTC)', 'Ms. Maxima P. Galvez', 'Tamaraw, La Curva, San Jose, Occidental Mindoro', '0930-119-6850'],
            ['2025-044SB', 'Kaunlaran Kasaganaan Kapayapaan ng mga Napapanahong Magsasaka (KKKNM)', 'Mr. Oscar A. Baren', 'San Jose, Occidental Mindoro', '0965-488-6381'],
            ['2025-045SB', 'Mercury Tricycle Operators and Drivers Association', 'Mr. Den Mark J. Aguilar', 'Poblacion IV, San Jose, Occidental Mindoro', '0966-600-7440'],
            ['2025-046SB', 'Bubog Development Cooperative', 'Mr. Victorino D. Juayno', 'Bubog, San Jose, Occidental Mindoro', '0948-833-0543'],
            ['2025-047SB', 'SJNP TODA-San Jose New Port TODA', 'Mr. Hermie Loyd Arnejo', 'Purok Maligaya, Caminawit, San Jose, Occidental Mindoro', '0951-104-4272'],
            ['2025-048SB', 'Concepcion Village TODA', 'Mr. Ricky Robles', 'Bubog, San Jose, Occidental Mindoro', '0985-258-0201'],
            ['2025-049SB', 'San Jose Balik-Balik Mapaya TODA (SJBBM TODA)', 'Mr. Regino D. Masangya', 'Mapaya III Boundary, San Jose, Occidental Mindoro', '0915-941-0796'],
            ['2025-050SB', 'San Jose OMECO Tricycle Operators and Drivers Association', 'Mr. Lemuel Bongcales', 'Poblacion VI, San Jose, Occidental Mindoro', '0910-627-1220'],
            ['2025-051SB', 'Samahang Magsasaka ng Bubog at San Agustin (SAMBA) Inc.', 'Ms. Leticia Garcia', 'Bubog, San Jose, Occidental Mindoro', '0994-487-4358'],
            ['2025-052SB', 'Samahang Magsasaka sa Molases Multi-Purpose Cooperative para sa Pritil (SAMAMUCO-PRI)', 'Mr. Josie P. Lapuz', 'Block I, Bubog, San Jose, Occidental Mindoro', '0999-995-9784'],
            ['2025-053SB', 'Murtha Agriculture Cooperative (MAC)', 'Mr. Ricardo A. Gimeno', 'Murtha, San Jose, Occidental Mindoro', '0966-491-9749'],
            ['2025-054SB', 'Central Transport Association', 'Mr. Ricky Mendoza', 'Central, San Jose, Occidental Mindoro', '0967-038-9730'],
            ['2025-055SB', 'Samahang Kababaihan ng Sitio Esperanza', 'Ms. Gina N. Arcega', 'Purok Esperanza, San Isidro, San Jose, Occidental Mindoro', '0912-051-8144'],
            ['2025-056SB', 'Samahan ng Tricycle Driver Operator', 'Mr. Alfredo S. Mina, III', 'Poblacion 8, San Jose, Occidental Mindoro', '0936-210-9289'],
            ['2025-057SB', 'San Jose Barangay I TODA (SJB1TODA)', 'Mr. Tirso M. Tayoto', 'San Jose, Occidental Mindoro', '0910-101-4930'],
            ['2025-058SB', 'San Jose Retired Teachers Association, Inc.', 'Ms. Corazon R. Calaguas', 'Mabini Street, Labangan Poblacion, San Jose, Occidental Mindoro', '0930-196-9067'],
            ['2025-059SB', 'Kabalikat Charity Civic Communicator Inc.', 'Mr. Christopher S. Yano', 'Guillermo Village, San Roque, San Jose, Occidental Mindoro', '0907-627-4421 / 0955-158-7653'],
            ['2025-060SB', 'Central Tricycle Operator Drivers Association', 'Mr. Zandro Herrera', 'Central, San Jose, Occidental Mindoro', '0907-940-7414'],
            ['2025-061SB', 'Babae Bayan Iisa (BABAI) San Roque II Chapter I', 'Ms. Charry B. Pamitan', 'San Roque II, San Jose, Occidental Mindoro', '0906-571-7835'],
            ['2025-062SB', 'Solo Parents Association of Barangay Pag-asa', 'Ms. Cecilia A. Mendoza', 'Pag-asa, San Jose, Occidental Mindoro', '0950-641-1452'],
            ['2025-063SB', 'M.A TODA', 'Mr. Bernie N. Mellendres', 'Mabini, San Jose, Occidental Mindoro', '0966-528-6223'],
            ['2025-064SB', 'Sa Agustin TODA', 'Ms. Victory B. Sualog', 'San Agustin, San Jose, Occidental Mindoro', '0948-900-7224'],
            ['2025-065SB', 'La Curva Tricycle Operators & Drivers Association', 'Mr. Jayson A. Emperador', 'La Curva, San Jose, Occidental Mindoro', '0948-388-9523'],
            ['2025-066SB', 'Bubog Relocation Site Tricycle Operators and Drivers Association', 'Mr. Ferdinand L. Sape', 'Bubog, San Jose, Occidental Mindoro', '0907-020-2699'],
            ['2025-067SB', 'San Jose Municipal Tricycle Operators and Drivers Association', 'Mr. George M. Ramos', 'Langka Road, DC Subdivision, San Jose, Occidental Mindoro', '0997-918-2900'],
            ['2025-068SB', 'La Curva Express TODA', 'Mr. Bonifacio D. Dela Cruz', 'La Curva, San Jose, Occidental Mindoro', '0946-607-5174'],
            ['2025-069SB', 'Caminawit Tagumpay 2 TODA', 'Mr. Nimrod S. Vidal', 'Caminawit, San Jose, Occidental Mindoro', '0948-549-7606'],
            ['2025-070SB', 'Bulacan, Sagisag ng Layon at Kapatiran Multi-Purpose Cooperative (BUSILAK)', 'Mr. Crisanto B. Discion', 'Purok 4, Magbay, San Jose, Occidental Mindoro', '0955-447-5406'],
            ['2025-071SB', 'Mendiola Campus Tricycle Operators and Drivers Association (MCTODA)', 'Mr. Carlos O. Tresvalles', 'Bagong Sikat, San Jose, Occidental Mindoro', '0969-048-2193'],
            ['2025-072SB', 'Rural Workers of Julo II Bubog Residence Association', 'MS. Maricel Ubaldo', 'Bubog, San Jose, Occidental Mindoro', '0977-697-9530'],
            ['2025-073SB', 'Gomez Village Tricycle Operator and Drivers Association', 'Mr. Arnel S. Venus', 'Gomez Village, Pag-asa, San Jose, Occidental Mindoro', '0910-252-1609'],
            ['2025-074SB', 'Caminawit -Ili Tricycle Operators and Drivers Association', 'Mr. Renan L. Malanao', 'Jacinto St., Poblacion 4, San Jose, Occidental Mindoro', '0950-155-8710'],
            ['2025-075SB', 'San Jose Poblacion Tricycle Operators and Drivers Association (SJPTODA)', 'Mr. Raymundo A. Ramos', 'Poblacion 3, San Jose, Occidental Mindoro', '0955-221-1663 / 0930-133-9761'],
            ['2025-076SB', 'San Jose Veterans TODA', 'Mr. Jester I. Martizano', 'Poblacion I, San Jose, Occidental Mindoro', '0953-338-8243'],
            ['2025-077SB', 'San Jose Concern Tricycle Operator and Driver Association', 'Ms. Virginia A. Benoza', 'Mapaya 3, San Jose, Occidental Mindoro', '0975-139-6871'],
            ['2025-078SB', 'Barangay 4 TODA', 'Mr. Solferino F. Titong', 'Poblacion 4, San Jose, Occidental Mindoro', '0909-145-9717'],
            ['2025-079SB', 'Sto. Niño Bubog Homeowners Association', 'Mr. Nelson H. Valiente', 'Purok Sto. Niño, Bubog, San Jose, Occidental Mindoro', '0948-945-5138'],
            ['2025-080SB', 'Labangan Campus San Jose Occidental Mindoro Tricycle Operators and Drivers Association', 'Mr. Joemarie P. Dumalang', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0930-541-9635'],
            ['2025-081SB', 'Bubog Express Tricycle Drivers Association', 'Mr. Joel A. Villanueva', 'Bubog, San Jose, Occidental Mindoro', '0970-604-2766 / 0975-850-6483'],
            ['2025-082SB', 'Tugtugin, Zigzag, Naitan Operators and Drivers Association (TUZINODA)', 'Ms. Heide D. Benoya', 'Batasan, San Jose, Occidental Mindoro', '0916-169-0227'],
            ['2025-083SB', 'Bagong Sikat A & B TODA', 'Mr. Francis Jones T. Cañaveral', 'Bagong Sikat, San Jose, Occidental Mindoro', '0997-148-1912'],
            ['2025-084SB', 'Ducalan II Labanga Tricycle Operators and Drivers Association (D II L TODA)', 'Mr. Dennis A. Gabuco', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0945-723-9341'],
            ['2025-085SB', 'S.M.B.T.V. 7 TODA', 'Mr. Rome Q. Mendoza', 'Purok 5, San Roque, San Jose, Occidental Mindoro', '0975-224-8497'],
            ['2025-086SB', 'Agupitan & Health Center TODA', 'Mr. Mar B. Egaña', 'Marsan Village, San Roque, San Jose, Occidental Mindoro', '0912-538-4266'],
            ['2025-087SB', 'Sampalok Singko TODA', 'Mr. Roberto Alvarez', 'Poblacion 5, San Jose, Occidental Mindoro', '0910-982-6441'],
            ['2025-088SB', 'Masagana A & Tagumpay B TODA', 'Mr. Alfredo O. Dayag', 'Bagong Sikat, San Jose, Occidental Mindoro', '0928-468-5549'],
            ['2025-089SB', 'Idol Tricycle Drivers Operators and Brotherhood Association (I.T.O.B.A.)', 'Mr. Ricky C. Yayen', 'San Jose, Occidental Mindoro', '0955-067-3701'],
            ['2025-090SB', 'Q.S TODA', 'Mr. Ruperto M. Absalon, Jr.', 'Quirino Street, Poblacion 7, San Jose, Occidental Mindoro', '0967-994-1588'],
            ['2025-091SB', 'MC Donald’s San Jose TODA', 'Mr. Rommel P. Bitoon', 'San Jose, Occidental Mindoro', '0963-849-0067'],
            ['2025-092SB', 'United San Jose Occidental Mindoro TODA (USJOM)', 'Mr. Joey M. Caldito', 'Poblacion 8, San Jose, Occidental Mindoro', '0948-213-5280'],
            ['2025-093SB', 'Magsaysay St and SB TODA', 'Mr. Nelson G. Sabado', 'Poblacion 7, San Jose, Occidental Mindoro', '0985-216-3350'],
            ['2025-094SB', 'San Isidro Tricycle Operators and Drivers Association', 'Mr. Jose L. Fernando', 'San Isidro, San Jose, Occidental Mindoro', '0951-661-3157'],
            ['2025-095SB', 'Caminawit Shorline Tricycle Operator Driver Associaton', 'Mr. Argie A. Labaja', 'Caminawit, San Jose, Occidental Mindoro', '0950-230-7441'],
            ['2025-096SB', 'Ufudan Mga Ina Buhid as Aligon Gamat', 'Ms. Rosita A. Montajes', 'Sitio Bato-Ili, Monteclaro, San Jose, Occidental Mindoro', '0935-426-7735'],
            ['2025-097SB', 'Airport Road Tricycle Operators Drivers Association', 'Mr. Antonio V. Vega', 'San Roque, San Jose, Occidental Mindoro', '0946-768-4174'],
            ['2025-098SB', 'Samahan ng mga Magbibilad', 'Mr. John Carlo E. Pastrana', 'Caminawit, San Jose, Occidental Mindoro', '0905-470-0728'],
            ['2025-099SB', 'Sta. Fe TODA', 'Mr. Joselito B. Andres', 'Sta. Fe, San Jose, Occidental Mindoro', '0910-938-5131'],
            ['2025-100SB', 'La Perla TODA', 'Mr. Rommel P. Garillo', 'Pag-asa, San Jose, Occidental Mindoro', '0905-566-2028'],
            ['2025-101SB', 'Bubog Transport Association', 'Mr. Jovito M. Flores', 'Bubog, San Jose, Occidental Mindoro', '0906-914-3176'],
            ['2025-102SB', 'Main Campus Tricycle Operators and Drivers Association', 'Mr. Michael S. Mesde', 'Poblacion 1, San Jose, Occidental Mindoro', '0907-688-2581'],
            ['2025-103SB', 'San Jose District Central Tricycle Operators and Drivers Association', 'Mr. Matias C. Cercaña', 'Poblacion 4, San Jose, Occidental Mindoro', '0967-277-5163'],
            ['2025-104SB', 'Mina De Oro Seed Growers Multi-Purpose Cooperative', 'Ms. Rosa D. Cruz', '2373 Felix Bernales St, San Roque, San Jose, Occidental Mindoro', '0998-547-1093 / 0918-669-2544'],
            ['2025-105SB', 'Riz-Gom TODA', 'Mr. Jesus J. Olpindo', 'Poblacion 1, San Jose, Occidental Mindoro', '0955-221-1113'],
            ['2025-106SB', 'Busuang Boundary Tricycle Operators and Drivers’ Association', 'Mr. Jimmy Z. Gregorio', 'Poblacion 2, San Jose, Occidental Mindoro', '0970-132-0735'],
            ['2025-107SB', 'Tricycle Operators and Drivers Association (Burgos St. TODA)', 'Mr. Renel G. Enero', 'Burgos Street, Pag-asa, San Jose, Occidental Mindoro', '0906-409-5833'],
            ['2025-108SB', 'Labangan Bo. Site Tricycle Operator Driver Association (LBSTODA)', 'Mr. Arman Lyster Fuentes', 'Purok Bo. Site, Labangan, San Jose, Occidental Mindoro', '0975-817-2072'],
            ['2025-109SB', 'San Jose Adventist Academy TODA', 'Mr. Romeo Tañeca', 'San Roque 2, San Jose, Occidental Mindoro', '0975-817-9315'],
            ['2025-110SB', 'Santos Sy TODA', 'Mr. Arnold V. Salibio', 'Bonifacio Street, Poblacion 2, San Jose, Occidental Mindoro', '0916-949-3109'],
            ['2025-111SB', 'Lamisian Womens Association', 'Ms. Myra Jean G. Vilchez', 'Sitio Lamis, San Agustin, San Jose, Occidental Mindoro', '0953-536-2928'],
            ['2025-112SB', 'San Jose Caminawit Palengke Tricycle Operators & Drivers Association (SJCPTODA)', 'Mr. Chito P. De Guzman', 'Pag-asa, San Jose, Occidental Mindoro', '0968-880-6083'],
            ['2025-113SB', 'Islander Tricycle Operators & Drivers Association', 'Mr. Oliver S. Santos', 'Pag-asa, San Jose, Occidental Mindoro', '0961-612-6360'],
            ['2025-114SB', 'Bubog Extension TODA', 'Mr. Ireneo C. Cadut', 'Bubog, San Jose, Occidental Mindoro', '0912-477-9587'],
            ['2025-115SB', 'Bubog-PNOC Lot Homeowners’ Association, Inc.', 'Ms. Paulita A. Roja', 'Purok Proper, Bubog, San Jose, Occidental Mindoro', '0975-203-1764'],
            ['2025-116SB', 'Lamis Fisherfolk Association', 'Mr. Joseph B. Bacuyong', 'Sitio Lamis, San Agustin, San Jose, Occidental Mindoro', '0963-364-2732 /  0953-536-0204'],
            ['2025-117SB', 'Caminawit Commercial Fishing Boat Association (CACOFBA)', 'Ms. Jovelyn O. Ciasico', 'Caminawit, San Jose, Occidental Mindoro', '0975-222-8006'],
            ['2025-118SB', 'Pag-asa Central School Tricycle Operator and Drivers Association', 'Mr. Miller R. Jamot', 'Pag-asa, San Jose, Occidental Mindoro', '0930-415-4426'],
            ['2025-119SB', 'Yaw-Yawi, Agri, Hospital Tricycle Operators, & Driver Association (YAHTODA)', 'Mr. Roland B. Mamungay', 'Sitio Yaw-Yawi, Murtha, San Jose, Occidental Mindoro', '0912-688-3080'],
            ['2025-120SB', 'Samahan ng Nagkakaisang Mag-aasin', 'Mr. Jeffrey V. Mateong, Jr.', 'Mapaya 3. San Jose, Occidental Mindoro', '0927-072-8747'],
            ['2025-121SB', 'Bantay Oksi Association', 'Ms. Gemma M. Tabian', 'Doña Consuelo Subdivision, Pag-asa, San Jose, Occidental Mindoro', '0977-108-0361 /  0916-346-1083'],
            ['2025-122SB', 'Curanta-D6-Artemia Multi-Purpose Cooperative', 'Ms. Ana Marie M. Ausan', 'Bubog, San Jose, Occidental Mindoro', '0970-320-0367'],
            ['2025-123SB', 'Tamaraw Highway SJ TODA', 'Mr. Pepito Lucas', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0997-880-7298'],
            ['2025-124SB', 'Batasan Monteclaro Tricycle Operator Drivers Association (SANCLAROTODA)', 'Mr. Edgardo Vicente', 'Monteclaro, San Jose, Occidental Mindoro', '0936-210-8050'],
            ['2025-125SB', 'San Roque II Elementary School (SR2ES) TODA', 'Mr. Jonald A. Bernal, Sr.', 'San Roque, San Jose, Occidental Mindoro', '0966-022-8877 /  0966-492-0722'],
            ['2025-126SB', 'Caminawit Small Fishing Association', 'Ms. Jermie M. Paz', 'Caminawit, San Jose, Occidental Mindoro', '0948-920-1066'],
            ['2025-127SB', 'Ipil Seaweeds Farmers and Fisherfolk Association', 'Ms. Delinia E. Subaan', 'Ipil, San Jose, Occidental Mindoro', '0953-204-8974'],
            ['2025-128SB', 'Sikatuna Caminawit TODA', 'Mr. Jordan U. Tatoy', 'Poblacion 2, San Jose, Occidental Mindoro', '0909-152-9130'],
            ['2025-129SB', 'Crossing TODA', 'Mr. Jovito L. Jayme, Jr.', 'Poblacion 7, San Jose, Occidental Mindoro', '0966-665-4653'],
            ['2025-130SB', 'Samahang Maralita ng Brgy. Bubog Association (S.M.B.B.A.)', 'Ms. Mary Gold Luis', 'Bubog, San Jose, Occidental Mindoro', '0995-791-4787'],
            ['2025-131SB', 'San Jose Pilot Elementary School TODA', 'Mr. Reynaldo O. Mamalayan', 'Poblacion 2, San Jose, Occidental Mindoro', '0910-001-2706'],
            ['2025-132SB', 'San Jose Public Health and Diagnostic Center (SJPHDCTODA)', 'Mr. Efren M. Embanacido', 'Poblacion 7, San Jose, Occidental Mindoro', '0930-415-4428'],
            ['2025-133SB', 'Mapaya 3 Boundary Tricycle Operators and Drivers Association (M3BTODA)', 'Mr. Bonifacio O. Maligo', 'Sitio Boundary, Mapaya 3, San Jose, Occidental Mindoro', '0964-963-6110'],
            ['2025-134SB', 'Busuanga Central Boundary (BCB TODA)', 'Mr. Cristilito G. Manguba', 'Central, San Jose, Occidental Mindoro', '0950-769-4135'],
            ['2025-135SB', 'San Jose Quezon Street TODA', 'Mr. Eugene M. Orcino', 'Poblacion 7, San Jose, Occidental Mindoro', '0935-934-7371'],
            ['2025-136SB', 'San Jose Municipal Town Plaza Tricycle Operator and Drivers Association', 'Mr. Monico S. Romero', 'Poblacion 7, San Jose, Occidental Mindoro', '0931-942-080'],
            ['2025-137SB', 'PCIC TODA', 'Mr. Freddie B. Radam', 'Bagong Silang, San Jose, Occidental Mindoro', '0953-176-7679'],
            ['2025-138SB', 'Bigkis ng Pag-asa Women’s Community Association', 'Ms. Shiela Marie N. Libunao', 'Pag-asa, San Jose, Occidental Mindoro', '0953-198-3249'],
            ['2025-139SB', 'Bantay Likas Yaman ng Tagumpay Association Inc.', 'Mr. Leodegario B. Valera, Jr.', 'Mapaya, San Jose, Occidental Mindoro', '0917-804-3146'],
            ['2025-140SB', 'Samahang Mandaragat', 'Mr. Randy B. de Lara', 'Pag-asa, San Jose, Occidental Mindoro', '0953-437-8156'],
            ['2025-141SB', 'San Isidro Upper Land Association', 'Mr. Arnel D. Bautista', 'San Isidro, San Jose, Occidental Mindoro', '09995-176-942'],
            ['2025-142SB', 'Jollibee Tricycle Operator and Driver’s Association', 'Mr. Daniel Laurencio', 'Jacinto Corner Rizal St., San Jose, Occidental Mindoro', '0912-355-0986'],
            ['2025-143SB', 'San Roque TODA', 'Mr. Cesar L. Ibojo', 'San Roque, San Jose, Occidental Mindoro', '0910-142-0092'],
            ['2025-144SB', 'Negros Women for Tomorrow Foundation, Inc.', 'Mr. Albert G. Salvador', 'General Lukban St., Brgy . 8, San Jose, Occidental Mindoro', '0963-804-5799'],
            ['2025-145SB', 'Sorosoro Ibaba Development Cooperative', 'Mr. Rico B. Geron', 'San Roque, San Jose, Occidental Mindoro', '0945-544-5303'],
            ['2025-146SB', 'Mapaya 1, Tricycle Operators and Drivers Association (MAP1TODA)', 'Mr. Mario Gene Q. Ducado', 'Sitio San Miguel, Mapaya 1, San Jose, Occidental Mindoro', '0991-834-5479'],
            ['2025-147SB', 'Samahan ng Kababaihan Ipil Ilin', 'Ms. Dorylin B. Embanecido', 'Ipil Ilin, San Jose, Occidental Mindoro', '0908-217-7620'],
            ['2025-148SB', 'Dorcas Community Foundation Incorporated of Mindoro', 'Ms. Edna A. Ysibido', 'Bubog Extension, San Jose, Occidental Mindoro', '0999-923-2633 / 0907-998-9505'],
            ['2025-149SB', '8 Keys Development Cooperative (8 KADECO)', 'Mr. Demetrio M. Vergara', 'Magbay, San Jose, Occidental Mindoro', '0956-896-7413'],
            ['2025-150SB', 'Unified Quezon 2 Neighborhood Association, Inc. (UNIQUE 2 NA INC.)', 'Ms. Mirasol S. Enano', 'Kanuping St., Purok Quezon 2, Bubog San Jose, Occidental Mindoro', '0927-9024-022'],
            ['2025-151SB', 'Caminawit Pag-asa Tricycle Operators and Drivers Association (CAMPTODA)', 'Ms. Emily T. Tanteo', 'Poblacion 5, San Jose, Occidental Mindoro', '0955-183-0950'],
            ['2025-152SB', 'Samahan ng Manggagawang Mindoreño Agriculture Cooperative (SAMMAGRICO)', 'Mr. Gerrywin S. Oquindo', 'Magbay, San Jose, Occidental Mindoro', '0995-292-7631'],
            ['2025-153SB', 'Bubog Poblacion TODA', 'Mr. Jay H. Valiente', 'Bubog, San Jose, Occidental Mindoro', '0935-650-4425'],
            ['2025-154SB', 'Labangan San Jose Tricycle Operation Drivers Association', 'Mr. Richard G. Marisga', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0981-751-6862 /  0985-878-7916'],
            ['2025-155SB', 'Alliance of Local Farmers Association', 'Mr. Darius F. Syquio', 'Bubog, San Jose, Occidental Mindoro', '0968-391-4770'],
            ['2026-156SB', 'Barangay 5 TODA', 'Mr. Ben-ami S. Jalos', 'Poblacion 5, San Jose, Occidental Mindoro', '0949-344-6734'],
            ['2026-157SB', 'Lakandula-Arellano-Bonifacio Tricycle Operators and Driver’s Association (LAB TODA)', 'Mr. Federico E. Silverio', 'Poblacion 3, San Jose, Occidental Mindoro', '0927-834-7656'],
            ['2026-158SB', 'Motortrade Personal Collection Tricycle Operators and Drivers Association', 'Mr. Richard E. Abejo', 'Poblacion 3, San Jose, Occidental Mindoro', '0935-890-1698'],
            ['2026-159SB', 'Mangarin Transport Service Association', 'Mr. Rozano Embanecido', 'Mangarin, San Jose, Occidental Mindoro', '0967-277-4787'],
            ['2026-160SB', 'Samahang Mangingisda ng Barangay San Agustin (MABARSA)', 'Mr. William P. Piastro', 'Main Barrio, San Agustin, San Jose, Occidental Mindoro', '0935-689-2128'],
            ['2026-161SB', 'Philippine Indigenous Farmers and Fisherfolks Agriculture Cooperative (PIFFACO)', 'Mr. Salvador A. Unilongo, Jr. II', 'Ducalan Uno, Labangan Poblacion, San Jose, Occidental Mindoro', '0963-780-3978 / 0997-880-0331'],
            ['2026-162SB', 'Sea Breeze TODA', 'Mr. Benjie D. Roldan', 'Purok 2, Pag-asa, San Jose, Occidental Mindoro', '0935-684-9736'],
            ['2026-163SB', 'Barangay II, Poblacion TODA (BPIITODA)', 'Ms. Derlinda V. Enriquez', 'Poblacion 2, San Jose, Occidental Mindoro', '0965-295-4990'],
            ['2026-164SB', 'Occidental Mindoro Methodist Agriculture Agro Reforestation and Industrial Development Association', 'Mr. Victorino G. Ruedas', 'Ma. Cristina St., Pag-asa, San Jose, Occidental Mindoro', '0930-963-4285'],
            ['2026-165SB', 'BMTODA', 'Mr. Joralden E. Rodriguez', 'Mabini, San Jose, Occidental Mindoro', '0918-928-6322'],
            ['2026-166SB', 'San Jose Unique Farmers Association (SJUFA)', 'Mr. Froilan T. Bañaga', 'Sitio Punzalan, Bayotbot, San Jose, Occidental Mindoro', '0906-514-2227'],
            ['2026-167SB', 'Pinagsamang Lakas ng Mangingisda Ng San Jose Occidental Mindoro (PILAK)', 'Mr. Senesidro A. Erison', 'Bubog, San Jose, Occidental Mindoro', '0956-478-7694'],
            ['2026-168SB', 'Mindoro Occidental Hog Raisers Agriculture Cooperative (OMHRACo)', 'Mr. Emiliano R. Villanada', 'Magbay, San Jose, Occidental Mindoro', '0947-835-8449'],
            ['2026-169SB', 'San Jose San Roque Pandurucan Tricycle Operators and Drivers Association (SJSPTODA)', 'Mr. Benjamin F. Pilon, III', 'San Roque I, San Jose, Occidental Mindoro', '0985-878-8295'],
            ['2026-170SB', 'San Jose Occidental Mindoro North and South Bound Grand Terminal TODA', 'Mr. Sofronio A. Roja', 'San Roque, San Jose, Occidental Mindoro', '0910-003-7786'],
            ['2026-171SB', 'MOTOLITE TODA', 'Mr. Danilo Salazar', 'Poblacion II, San Jose, Occidental Mindoro', '0975-298-4876'],
            ['2026-172SB', 'PEPSI TODA', 'Mr. Edgardo B. Lumalang', 'Doña Consuelo Subdivision, Pag-asa, San Jose, Occidental Mindoro', '0929-509-8335'],
            ['2026-173SB', 'Bubog Aquafil Farmers Association (BAFA)', 'Ms. Luzviminda S. Sagnip', 'Bubog, San Jose, Occidental Mindoro', '0907-985-3081'],
            ['2026-174SB', 'Jollibee Labangan TODA', 'Mr. Remelito L. Fabroa', 'Labangan Poblacion, San Jose, Occidental Mindoro', '0994-109-8909'],
            ['2026-175SB', 'Bagong Sikat Tagumpay A TODA', 'Mr. Robert B. Peralta', 'Tagumpay A, Bagong Sikat, San Jose, Occidental Mindoro', '0915-222-9335'],
            ['2026-176SB', 'Pamilya Tayo Homeowners’ Association, Inc. (PATAHANAN HOA, INC.)', 'Mr. Gregorio A. Sidamon', 'Purok 6-B, San Roque, San Jose, Occidental Mindoro', '0909-145-6220'],
            ['2026-177SB', 'Pagkakaisa ng Farmers and Fisherfolks ng Barangay Natandol Association (PAFARFINAS)', 'Mr. Rolando C. Espeso', 'Natandol, San Jose, Occidental Mindoro', '0955-937-9443'],
        ];


        /*
         * Publicly documented representative points for San Jose barangays.
         *
         * These are barangay-level map references, NOT verified door-level
         * coordinates for each individual CSO office. The accreditation
         * register supplies street/sitio addresses but no GPS coordinates.
         *
         * Primary coordinate reference: published study sampling-point
         * coordinates for San Jose barangays (Sustainability 2023, 15, 3667,
         * Appendix A); supplementary barangay coordinates from PhilAtlas.
         * Where multiple study points exist, a representative documented
         * point is used. Do not treat these as surveyed office locations.
         */
        $barangayCoordinates = [
            'ambulong' => [12.222300, 121.023700],
            'bagong sikat' => [12.367000, 121.069000],
            'batasan' => [12.527000, 121.119000],
            'bayotbot' => [12.405000, 121.098000],
            'bubog' => [12.366000, 121.038000],
            'caminawit' => [12.331900, 121.082500],
            'central' => [12.434000, 121.048000],
            'gomez village' => [12.353000, 121.066000],
            'ipil ilin' => [12.253000, 121.091000],
            'ipil' => [12.253000, 121.091000],
            'la curva' => [12.410000, 121.082000],
            'labangan poblacion' => [12.361000, 121.078000],
            'labangan' => [12.361000, 121.078000],
            'mabini' => [12.372000, 121.086000],
            'magbay' => [12.409000, 121.089000],
            'mangarin' => [12.360000, 121.102000],
            'mapaya' => [12.355000, 121.126200],
            'monteclaro' => [12.547000, 121.125000],
            'monte claro' => [12.547000, 121.125000],
            'murtha' => [12.440000, 121.103000],
            'natandol' => [12.184000, 121.106000],
            'pag-asa' => [12.352900, 121.066200],
            'poblacion 1' => [12.353000, 121.066000],
            'poblacion i' => [12.353000, 121.066000],
            'poblacion 2' => [12.353000, 121.066000],
            'poblacion ii' => [12.353000, 121.066000],
            'poblacion 3' => [12.356000, 121.066000],
            'poblacion 4' => [12.351000, 121.061000],
            'poblacion iv' => [12.351000, 121.061000],
            'poblacion 5' => [12.353000, 121.066000],
            'poblacion 6' => [12.353000, 121.066000],
            'poblacion vi' => [12.353000, 121.066000],
            'poblacion 7' => [12.353000, 121.066000],
            'poblacion 8' => [12.353000, 121.066000],
            'brgy . 8' => [12.353000, 121.066000],
            'brgy 8' => [12.353000, 121.066000],
            'barangay 8' => [12.353000, 121.066000],
            'san agustin' => [12.444000, 121.022000],
            'san isidro' => [12.417000, 121.057000],
            'san roque' => [12.358000, 121.051000],
            'sta. fe' => [12.352900, 121.066200],
        ];

        $resolveCoordinates = static function (string $address) use ($barangayCoordinates): array {
            $normalized = mb_strtolower($address);

            // Match the most specific barangay/locality labels first.
            $keys = array_keys($barangayCoordinates);
            usort($keys, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

            foreach ($keys as $locality) {
                if (str_contains($normalized, $locality)) {
                    return $barangayCoordinates[$locality];
                }
            }

            // San Jose municipality reference point. This is only a fallback
            // for addresses whose locality cannot be confidently classified.
            return [12.352900, 121.066200];
        };

        $categoryAssignments = [
            '2025-001SB' => 'Water & Sanitation Services',
            '2025-002SB' => 'Agriculture & Agrarian Services',
            '2025-003SB' => 'Agriculture & Agrarian Services',
            '2025-004SB' => 'Social Welfare & Community Services',
            '2025-005SB' => 'Social Welfare & Community Services',
            '2025-006SB' => 'Trade, Industry & Enterprise',
            '2025-007SB' => 'Financial & Social Security Services',
            '2025-008SB' => 'Agriculture & Agrarian Services',
            '2025-009SB' => 'Agriculture & Agrarian Services',
            '2025-010SB' => 'Social Welfare & Community Services',
            '2025-011SB' => 'Agriculture & Agrarian Services',
            '2025-012SB' => 'Trade, Industry & Enterprise',
            '2025-013SB' => 'Agriculture & Agrarian Services',
            '2025-014SB' => 'Agriculture & Agrarian Services',
            '2025-015SB' => 'Trade, Industry & Enterprise',
            '2025-016SB' => 'Agriculture & Agrarian Services',
            '2025-017SB' => 'Transportation & Aviation',
            '2025-018SB' => 'Transportation & Aviation',
            '2025-019SB' => 'Transportation & Aviation',
            '2025-020SB' => 'Transportation & Aviation',
            '2025-021SB' => 'Transportation & Aviation',
            '2025-022SB' => 'Transportation & Aviation',
            '2025-023SB' => 'Transportation & Aviation',
            '2025-024SB' => 'Transportation & Aviation',
            '2025-025SB' => 'Transportation & Aviation',
            '2025-026SB' => 'Transportation & Aviation',
            '2025-027SB' => 'Transportation & Aviation',
            '2025-028SB' => 'Transportation & Aviation',
            '2025-029SB' => 'Transportation & Aviation',
            '2025-030SB' => 'Trade, Industry & Enterprise',
            '2025-031SB' => 'Trade, Industry & Enterprise',
            '2025-032SB' => 'Agriculture & Agrarian Services',
            '2025-033SB' => 'Social Welfare & Community Services',
            '2025-034SB' => 'Social Welfare & Community Services',
            '2025-035SB' => 'Trade, Industry & Enterprise',
            '2025-036SB' => 'Agriculture & Agrarian Services',
            '2025-037SB' => 'Labor & Employment',
            '2025-038SB' => 'Financial & Social Security Services',
            '2025-039SB' => 'Agriculture & Agrarian Services',
            '2025-040SB' => 'Trade, Industry & Enterprise',
            '2025-041SB' => 'Social Welfare & Community Services',
            '2025-042SB' => 'Social Welfare & Community Services',
            '2025-043SB' => 'Social Welfare & Community Services',
            '2025-044SB' => 'Agriculture & Agrarian Services',
            '2025-045SB' => 'Transportation & Aviation',
            '2025-046SB' => 'Trade, Industry & Enterprise',
            '2025-047SB' => 'Transportation & Aviation',
            '2025-048SB' => 'Transportation & Aviation',
            '2025-049SB' => 'Transportation & Aviation',
            '2025-050SB' => 'Transportation & Aviation',
            '2025-051SB' => 'Agriculture & Agrarian Services',
            '2025-052SB' => 'Agriculture & Agrarian Services',
            '2025-053SB' => 'Agriculture & Agrarian Services',
            '2025-054SB' => 'Transportation & Aviation',
            '2025-055SB' => 'Social Welfare & Community Services',
            '2025-056SB' => 'Transportation & Aviation',
            '2025-057SB' => 'Transportation & Aviation',
            '2025-058SB' => 'Social Welfare & Community Services',
            '2025-059SB' => 'Social Welfare & Community Services',
            '2025-060SB' => 'Transportation & Aviation',
            '2025-061SB' => 'Social Welfare & Community Services',
            '2025-062SB' => 'Social Welfare & Community Services',
            '2025-063SB' => 'Transportation & Aviation',
            '2025-064SB' => 'Transportation & Aviation',
            '2025-065SB' => 'Transportation & Aviation',
            '2025-066SB' => 'Transportation & Aviation',
            '2025-067SB' => 'Transportation & Aviation',
            '2025-068SB' => 'Transportation & Aviation',
            '2025-069SB' => 'Transportation & Aviation',
            '2025-070SB' => 'Trade, Industry & Enterprise',
            '2025-071SB' => 'Transportation & Aviation',
            '2025-072SB' => 'Social Welfare & Community Services',
            '2025-073SB' => 'Transportation & Aviation',
            '2025-074SB' => 'Transportation & Aviation',
            '2025-075SB' => 'Transportation & Aviation',
            '2025-076SB' => 'Transportation & Aviation',
            '2025-077SB' => 'Transportation & Aviation',
            '2025-078SB' => 'Transportation & Aviation',
            '2025-079SB' => 'Social Welfare & Community Services',
            '2025-080SB' => 'Transportation & Aviation',
            '2025-081SB' => 'Transportation & Aviation',
            '2025-082SB' => 'Transportation & Aviation',
            '2025-083SB' => 'Transportation & Aviation',
            '2025-084SB' => 'Transportation & Aviation',
            '2025-085SB' => 'Transportation & Aviation',
            '2025-086SB' => 'Transportation & Aviation',
            '2025-087SB' => 'Transportation & Aviation',
            '2025-088SB' => 'Transportation & Aviation',
            '2025-089SB' => 'Transportation & Aviation',
            '2025-090SB' => 'Transportation & Aviation',
            '2025-091SB' => 'Transportation & Aviation',
            '2025-092SB' => 'Transportation & Aviation',
            '2025-093SB' => 'Transportation & Aviation',
            '2025-094SB' => 'Transportation & Aviation',
            '2025-095SB' => 'Transportation & Aviation',
            '2025-096SB' => 'Social Welfare & Community Services',
            '2025-097SB' => 'Transportation & Aviation',
            '2025-098SB' => 'Agriculture & Agrarian Services',
            '2025-099SB' => 'Transportation & Aviation',
            '2025-100SB' => 'Transportation & Aviation',
            '2025-101SB' => 'Transportation & Aviation',
            '2025-102SB' => 'Transportation & Aviation',
            '2025-103SB' => 'Transportation & Aviation',
            '2025-104SB' => 'Agriculture & Agrarian Services',
            '2025-105SB' => 'Transportation & Aviation',
            '2025-106SB' => 'Transportation & Aviation',
            '2025-107SB' => 'Transportation & Aviation',
            '2025-108SB' => 'Transportation & Aviation',
            '2025-109SB' => 'Transportation & Aviation',
            '2025-110SB' => 'Transportation & Aviation',
            '2025-111SB' => 'Social Welfare & Community Services',
            '2025-112SB' => 'Transportation & Aviation',
            '2025-113SB' => 'Transportation & Aviation',
            '2025-114SB' => 'Transportation & Aviation',
            '2025-115SB' => 'Social Welfare & Community Services',
            '2025-116SB' => 'Agriculture & Agrarian Services',
            '2025-117SB' => 'Agriculture & Agrarian Services',
            '2025-118SB' => 'Transportation & Aviation',
            '2025-119SB' => 'Transportation & Aviation',
            '2025-120SB' => 'Agriculture & Agrarian Services',
            '2025-121SB' => 'Social Welfare & Community Services',
            '2025-122SB' => 'Agriculture & Agrarian Services',
            '2025-123SB' => 'Transportation & Aviation',
            '2025-124SB' => 'Transportation & Aviation',
            '2025-125SB' => 'Transportation & Aviation',
            '2025-126SB' => 'Agriculture & Agrarian Services',
            '2025-127SB' => 'Agriculture & Agrarian Services',
            '2025-128SB' => 'Transportation & Aviation',
            '2025-129SB' => 'Transportation & Aviation',
            '2025-130SB' => 'Social Welfare & Community Services',
            '2025-131SB' => 'Transportation & Aviation',
            '2025-132SB' => 'Transportation & Aviation',
            '2025-133SB' => 'Transportation & Aviation',
            '2025-134SB' => 'Transportation & Aviation',
            '2025-135SB' => 'Transportation & Aviation',
            '2025-136SB' => 'Transportation & Aviation',
            '2025-137SB' => 'Transportation & Aviation',
            '2025-138SB' => 'Social Welfare & Community Services',
            '2025-139SB' => 'Environment & Natural Resources',
            '2025-140SB' => 'Agriculture & Agrarian Services',
            '2025-141SB' => 'Social Welfare & Community Services',
            '2025-142SB' => 'Transportation & Aviation',
            '2025-143SB' => 'Transportation & Aviation',
            '2025-144SB' => 'Social Welfare & Community Services',
            '2025-145SB' => 'Trade, Industry & Enterprise',
            '2025-146SB' => 'Transportation & Aviation',
            '2025-147SB' => 'Social Welfare & Community Services',
            '2025-148SB' => 'Social Welfare & Community Services',
            '2025-149SB' => 'Trade, Industry & Enterprise',
            '2025-150SB' => 'Social Welfare & Community Services',
            '2025-151SB' => 'Transportation & Aviation',
            '2025-152SB' => 'Agriculture & Agrarian Services',
            '2025-153SB' => 'Transportation & Aviation',
            '2025-154SB' => 'Transportation & Aviation',
            '2025-155SB' => 'Agriculture & Agrarian Services',
            '2026-156SB' => 'Transportation & Aviation',
            '2026-157SB' => 'Transportation & Aviation',
            '2026-158SB' => 'Transportation & Aviation',
            '2026-159SB' => 'Transportation & Aviation',
            '2026-160SB' => 'Agriculture & Agrarian Services',
            '2026-161SB' => 'Agriculture & Agrarian Services',
            '2026-162SB' => 'Transportation & Aviation',
            '2026-163SB' => 'Transportation & Aviation',
            '2026-164SB' => 'Agriculture & Agrarian Services',
            '2026-165SB' => 'Transportation & Aviation',
            '2026-166SB' => 'Agriculture & Agrarian Services',
            '2026-167SB' => 'Agriculture & Agrarian Services',
            '2026-168SB' => 'Agriculture & Agrarian Services',
            '2026-169SB' => 'Transportation & Aviation',
            '2026-170SB' => 'Transportation & Aviation',
            '2026-171SB' => 'Transportation & Aviation',
            '2026-172SB' => 'Transportation & Aviation',
            '2026-173SB' => 'Agriculture & Agrarian Services',
            '2026-174SB' => 'Transportation & Aviation',
            '2026-175SB' => 'Transportation & Aviation',
            '2026-176SB' => 'Social Welfare & Community Services',
            '2026-177SB' => 'Agriculture & Agrarian Services',
        ];

        $inserted = 0;
        $updated = 0;
        $restored = 0;
        $unchanged = 0;
        $contactsCreated = 0;
        $contactsUpdated = 0;

        DB::transaction(function () use (
            $organizations,
            $resolveCoordinates,
            $categoryAssignments,
            &$inserted,
            &$updated,
            &$restored,
            &$unchanged,
            &$contactsCreated,
            &$contactsUpdated
        ): void {
            /*
             * Resolve the NGO type locally so this seeder does not depend on
             * AgencyTypeSeeder being called first.
             */
            $ngoType = AgencyType::firstOrCreate(['name' => 'NGO']);

            /*
             * Assign each organization to a service/sector category.
             *
             * These are NOT organization-type labels. They use the existing
             * categories from TemporaryCategorySeeder and are based on the
             * organization's published name/sector in the accreditation list.
             *
             * The accreditation number is used only as a stable source key.
             */


            $requiredCategoryNames = array_values(array_unique(
                $categoryAssignments
            ));

            $categories = Category::withTrashed()
                ->whereIn('category_name', $requiredCategoryNames)
                ->get()
                ->keyBy('category_name');

            $missingCategories = array_values(array_diff(
                $requiredCategoryNames,
                $categories->keys()->all()
            ));

            if ($missingCategories) {
                throw new RuntimeException(
                    'The following categories are missing: '
                    . implode(', ', $missingCategories)
                    . '. Run TemporaryCategorySeeder first. '
                    . 'TemporaryNgoSeeder does not create categories.'
                );
            }

            // Restore only categories required by this dataset if they are trashed.
            foreach ($categories as $category) {
                if ($category->trashed()) {
                    $category->restore();
                }
            }

            /*
             * "Phone" is intentionally used instead of assuming every
             * published contact is a hotline or landline. The source only
             * labels the column "CONTACT NO.".
             */
            $phoneType = ContactType::updateOrCreate(
                ['slug' => 'phone'],
                [
                    'name' => 'Phone',
                    'is_active' => true,
                    'sort_order' => 6,
                ]
            );

            $resolveAbbreviation = static function (string $organizationName): string {
                if (preg_match('/\\(([^()]*)\\)/u', $organizationName, $matches)) {
                    $publishedAbbreviation = trim($matches[1]);
                    if ($publishedAbbreviation !== '') {
                        return $publishedAbbreviation;
                    }
                }

                $stopWords = [
                    'and', 'of', 'the', 'ng', 'mga', 'sa', 'at', 'para',
                    'nang', 'na', 'for', 'in', 'by',
                ];

                $words = preg_split('/[^\\p{L}\\p{N}]+/u', $organizationName, -1, PREG_SPLIT_NO_EMPTY);
                $initials = '';

                foreach ($words as $word) {
                    $normalizedWord = mb_strtolower($word);
                    if (in_array($normalizedWord, $stopWords, true)) {
                        continue;
                    }

                    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                }

                return $initials !== ''
                    ? $initials
                    : mb_strtoupper(mb_substr($organizationName, 0, 12));
            };

            foreach ($organizations as $row) {
                [$accreditationNo, $name, $chairman, $address, $contactNo] = $row;

                $categoryName = $categoryAssignments[$accreditationNo] ?? null;

                if (!$categoryName || !$categories->has($categoryName)) {
                    throw new RuntimeException(
                        "No valid service category assigned for {$accreditationNo} - {$name}."
                    );
                }

                $agencyData = [
                    'agency_name' => $name,
                    'agency_abbreviation' => $resolveAbbreviation($name),
                    'agency_type_id' => $ngoType->id,
                    'category_id' => $categories->get($categoryName)->id,
                    'agency_location' => $address . ', Philippines',
                    'agency_description' =>
                        'Civil society organization accredited by the Sangguniang Bayan '
                        . 'of the Municipality of San Jose, Occidental Mindoro, as listed '
                        . 'in the official Accredited CSO 2025-2028 record.',
                    'office_head_name' => $chairman,
                    'office_head_position' => 'Chairman',
                ];

                [$latitude, $longitude] = $resolveCoordinates($address);
                $agencyData['lat'] = $latitude;
                $agencyData['lng'] = $longitude;

                /*
                 * Match by the source's unique accreditation number, not by
                 * a potentially duplicated or similarly named organization.
                 */
                $agency = Agency::withTrashed()
                    ->where('agency_abbreviation', $accreditationNo)
                    ->first();

                /*
                 * Older revisions temporarily stored the accreditation
                 * number in agency_abbreviation. Once corrected to the
                 * organization's actual abbreviation, future runs must still
                 * find the same record instead of inserting a duplicate.
                 */
                if (!$agency) {
                    $agency = Agency::withTrashed()
                        ->where('agency_name', $name)
                        ->first();
                }

                $organizationChanged = false;

                if (!$agency) {
                    $agency = Agency::create($agencyData + [
                        'services_offered' => null,
                        'office_hours' => null,
                    ]);

                    $inserted++;
                    $organizationChanged = true;
                } else {
                    if ($agency->trashed()) {
                        $agency->restore();
                        $restored++;
                        $organizationChanged = true;
                    }

                    $agency->fill($agencyData);

                    if ($agency->isDirty()) {
                        $agency->save();
                        $updated++;
                        $organizationChanged = true;
                    }
                }

                /*
                 * The accreditation register provides a generic CONTACT NO.
                 * field. Store each published number as a Phone contact rather
                 * than misclassifying it as a hotline or landline.
                 *
                 * Multiple numbers separated by "/" become separate contacts
                 * so each can be called independently from the public profile.
                 * Only records owned by this seeder are synchronized; other
                 * administrator-managed contacts are preserved.
                 */
                $phoneNumbers = collect(preg_split('/\\s*\\/\\s*/', $contactNo))
                    ->map(static fn (string $number): string => trim($number))
                    ->filter()
                    ->values();

                $seededContactLabels = [];

                foreach ($phoneNumbers as $index => $phoneNumber) {
                    $label = $index === 0
                        ? 'Accreditation contact number'
                        : 'Alternate contact number ' . ($index + 1);

                    $seededContactLabels[] = $label;

                    $contact = AgencyContact::where('agency_id', $agency->id)
                        ->where('contact_type_id', $phoneType->id)
                        ->where('label', $label)
                        ->first();

                    $contactData = [
                        'agency_id' => $agency->id,
                        'contact_type_id' => $phoneType->id,
                        'label' => $label,
                        'value' => $phoneNumber,
                        'is_primary' => $index === 0,
                        'sort_order' => $index + 1,
                    ];

                    if (!$contact) {
                        AgencyContact::create($contactData);
                        $contactsCreated++;
                        $organizationChanged = true;
                    } else {
                        $contact->fill($contactData);

                        if ($contact->isDirty()) {
                            $contact->save();
                            $contactsUpdated++;
                            $organizationChanged = true;
                        }
                    }
                }

                // Remove only obsolete contacts previously created by this seeder.
                $obsoleteSeederContacts = AgencyContact::where('agency_id', $agency->id)
                    ->where('contact_type_id', $phoneType->id)
                    ->where(function ($query) {
                        $query->where('label', 'Accreditation contact number')
                            ->orWhere('label', 'like', 'Alternate contact number %');
                    })
                    ->whereNotIn('label', $seededContactLabels)
                    ->get();

                if ($obsoleteSeederContacts->isNotEmpty()) {
                    AgencyContact::whereIn('id', $obsoleteSeederContacts->pluck('id'))->delete();
                    $organizationChanged = true;
                }

                if (!$organizationChanged) {
                    $unchanged++;
                }
            }
        });

        $this->command?->info('Temporary NGO seeder completed.');
        $this->command?->info('Organizations in source: ' . count($organizations));
        $this->command?->info("Inserted: {$inserted}");
        $this->command?->info("Updated: {$updated}");
        $this->command?->info("Restored: {$restored}");
        $this->command?->info("Unchanged: {$unchanged}");
        $this->command?->info("Phone contacts created: {$contactsCreated}");
        $this->command?->info("Phone contacts updated: {$contactsUpdated}");
    }
}
