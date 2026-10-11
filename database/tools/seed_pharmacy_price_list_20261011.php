<?php

declare(strict_types=1);

require_once __DIR__ . '/MigrationManager.php';

DatabaseSafety::requireCli();

$raw = <<<'PRICE_LIST'
Tab Alendronic Acid 70mg					4,000
Tab Amlodipine 10mg					700
Tab Amlodipine 5mg					500
Tab Aceclofenac 					300
Tab Atenolol 100mg					
Tab Atenolol 50mg					
Tab Atorvastatin 40mg					500
Tab Atorvastatin 20mg					500
Tab Atorvastatin 10mg					300
Tab Aspirin 75mg					500
Tab Augmentin 625mg					2000
Tab Augmentin 1g					2,500
Tab Bendrofemethiazide					
Tab Bisoprolol 10mg					700
Tab Bisoprolol 5mg					500
Tab Bisoprolol 2.5mg					500
Tab Buscopan 10mg					500
Tab Cimetidine 400mg					500
Tab Co-codamol 8/500mg					1,000
Tab Co-codamol 30/500mg					1,200
Tab Albendazole					500
Tab Riconia Forte					800
Tab  Pregnacare					
Tab Daravit woman					500
Tab Evenine Primerose					800
Tab Rabeprazole 20mg					500
Tab Rabeprazole /Domperidone					500
Tab Esofag-D					500
Tab Vonoprazan					2,000
Tab Duphaston					1,000
Syr Marcfix					8,000
Syr Fleming					8,000
Syr Calcicare 100mls					20,000
Syr Calcicare 200mls					25,000
Syr Lincomycin					8,000
Syr Vitamin C					5,000
Syr Erythomycin					5,000
Syr Sporidex					8,000
Syr Cefpodoxime					8,000
Syr Ibuprofen					5,000
Syr Paracetamol					5,000
Syr Flagyl					5,000
Syr cefuroxime					5,000
Tab Zinc 20mg					300
Tab Zinc 50mg					500
Tab Zinc 100mg					500
Cap Tramadol 50mg					700
Cap Tamsulocin					700
Cap Rifampicin 300mg					700
Cap Loperamide 2mg					300
Cap Loperamide 4mg					500
Cap Lincomycin					700
Cap Flumox					700
Cap Amoxicillin 250mg					300
Cap Amoxicillin 500mg					500
Cap Doxycyline 100mg					500
Cap Fluconazole 200mg					500
Cap Fluconazole 150mg					500
Cap Dalacin C 300mg					2,500
Cap Dalacin C 150mg					1700
Cap Chloramphenicol 250mg					300
Cap Celebrex 200mg					500
Cap Selenium 200mcg					500
Cap indocid					
Tab Loratadine 10mg					500
Tab Losartan 50 mg					500
Tab Losartan + HCT					700
Tab Carvedilol 25mg					
Tab Calvit-D					
Tab Cal-D3					500
Tab Cal-D3 Plus					500
Tab Cal-D3 Max					700
Tab Clopidogrel 75mg					500
Tab Cefuroxime 500mg					800
Tab Cefuroxime 250mg					500
Tab Ciprofloxacin 500mg					500
Tab Ciprofloxacin 250mg					
Tab Chymoral 50,000iu					500
Tab Ocefix 400mg					800
Tab Ocefix 200mg					800
Tab Centrum					
Tab Clarithromycin 500mg					500
Tab Dopamine 250mg					500
Tab Doxazocin 4mg					1200
Tab Doxazocin 2mg					1000
Tab Diamicron 30mg					
Tab Gliclazide 80mg					
Tab Diazepam 5mg					500
Tab Nitrazepam 5mg					500
Tab Ethambutol 400mg					500
Tab Esomeprazole 400mg					500
Tab Fedolate 200mg					100
Tab Erythromycin 500mg 					500
Tab Flagyl 200mg					200
Tab Frusemide 20mg					500
Tab Frusemide 40mg					500
Tab Gelucil					100
Tab Gabapentin 300mg					500
Tab Vitamin A 2,00,000iu					1000
Tab Vitamin A 10,000iu					500
Tab Vitamin B Complex					100
Tab Vitamin C 100mg					150
Tab Vitamin C 500mg					500
Tab Vitamin C 1g					800
Tab Vitamin E 1000iu					500
Tab Multivite					100
Cap MIM Multivitamins					500
Cap Reload Immunity					500
Tab Immun-Active					800
Cap Lysomin					500
Cap Randeron-12					800
Gran. Aminogard					500
Tab Warfarin 5mg					500
Tab Warfarin 3mg					500
Tab Zithromax 250mg					1500
Tab Methotrexate 25mg					1000
Tab Metolazone 2.5mg					500
Tab Metolazone 5mg					500
Tab Metoclopramide 10mg					500
Tab Methocarcamol					500
Tab Metformin 1000mg					800
Tab Metformin 500mg					500
Tab Neurovite					500
Tab Norfloxacin					500
Tab Oxybutylin					1500
Tab Ofloxacin 400mg					500
Tab Ofloxacin 200mg					500
Tab Olanzepine 10mg					500
Tab Olanzepine 5mg					
Tab Orelox					50000 Per pack
Tab Prednisolone					150
Tab Perfloxacin 400mg					1000
Tab Pradaxa 40mg					2000
Tab Paracetamol 500mg					100
Tab Piriton					300
Tab Ramipril 10mg					700
Tab Rosuvastatin 20mg					500
Tab Rosuvastatin 10mg					500
Tab Salbutamol 4mg					500
Tab Fansidar					5000
Tab Spironolactone 25mg					700
Tab Stugeron					500
Tab Secnizole					2500
Tab Telmisartan 80mg					800
Tab Telmisartan 40mg					500
Tab Tinidazole 500mg					500
Tab Tadalafil 20mg					500
Tab Sinufed					500
Tab Triviamet					700
Sol. Dynamogen					1000
Cap Omeprazole 20mg					500
Cap Antalgex-T					1000
Tab Thiotic Acid 600mg					500
Tab Diclofenac 100mg					500
Tab Diclofenac 50mg					300
Tab Alphabetic					850
Cap Codliver Oil					500
Tab Vitamin A&E					800
Tab Artemether/Lumefantrine 80/480mg					5000
Tab Artemether/Lumefantrine 20/120mg					300
Tab Ibasunate 40/320mg					5000
Sol. Tot'hema 					100
Blood Tonics					5000
Tab Lisinopril 5mg					300
Tab Gramocef					1500
Tab Lisinopril 10mg					500
Tab Hydrochlorothiazide (Hydrex)					300
Tab Bendri Flumantine 2.5mg					
Tab Indapamide 1.5mg					800
Tab Natrixam					
Tab Telmisartan					
Tab Telmisartan/Hydrochlorothiazide					
Tab Atenolol 30mg					
Tab Clopidogrel 					500
Tab Labetolol 100mg					
Tab Amlodipine 2.5mg					500
Tab Xarelto 15mg					3500
Tab Xarelto 20mg					4000
Tab Zinc 50mg					300
Caustic Pen					35000
Tab Isomazide					300
Tab Pyrazinamide					300
Tab Amatem Soft Gel					5000
Tab Coatherm					5000
Tab Valsartan 80mg					700
Tab Valsartan 160mg					700
Tab Eliquis 5mg					500
Tab Amitriptyline 25mg					500
Cap Clindamycin 150mg					500
Cap Clindamycin 300mg					700
Tab Cefpodoxime 200mg					2000
Tab Linezolide 600mg					5000
Tab Moxiget					3000
Tab Tavanic 500mg					5000
Tab Vitamin D3 50,000iu					6000
Tab Vitamin D3 10,000iu					3000
Tab Vitamin D3 5,000iu					2500
Tab Vitamin D3 1,000iu					1500
Tab Xarelto 10mg					3000
Cap Livolin					850
Tab Tocovid					1000
Tab Uperio 200mg					3000
Tab Zinnat 500mg					3000
Gran Pylocare					2000
Gran Floranorm					15000
Tab Risperidone					700
Gran Epsom Salt					1500
Tab Vasoprim 75mg					200
Syr Multivite					5000
Cap Chloramphenicol (divide) 150g					700
Tab Ibuprofen Dispersible					500
Tab Renerve Pluse					900
Tab D-Artepp 80/640mg					7000
Tab Naproxen					500
Tab Amoxiclav 625mg					1000
Tab Amoxiclav 1g					1200
Gran. ORS					500
Tab Torsemide 10mg					300
Tab Torsemide 20mg					500
Tab Torsemide 100mg					500
Tab Levofloxacin 750mg					1000
Tab Levofloxacin 500mg					800
Tab Marcfix 325mg					1500
Tab Amlodipine/Bisoprolol					700
Tab Dapagliflozin 10mg					1500
Tab Dapagliflozin 5mg					700
Tab Linagliptin					800
Tab Vildagliptin					1500
Tab Feed Fine					500
Cap Pregabalin 75mg					500
Cap Tamsulosin/Dutasteride					800
Tab Hydroxyurea					800
Cap Tranexamic Acid					800
Tab Galvusmet					1000
Tab Meditriol 0.25mg					1500
Tab Gezlox					800
Tab Fleming 625mg					15000 Per Pack
Tab Fleming 1g					20000 Per Pack
Esofag-Kit					34000
Tab Cognitol					500
Tab Ginko Baloba					1000
Salonpas Patch					6000
Salonpas Spray					20000
A&V Cutaneous Stick					20000
Sufra-tulle					3000
Crepe Bandages					2500
Providone-iodin solution					25000
Drez Solution					30000
Drez Paste					15000
Mupirocin Ointment					10000
Lidocaine Cream 					5000
Diclomol Cream					5000
Power Heat					5000
Dermazine Cream					7000
Hydrosortisone Cream					5000
Liquid Paraffin					5000
Coloprep					30000
Inj Pentazocine					4500
Inj Paracetamol					2500
Infusion Paracetamol 1g					5000
Inj Quinine					3000
Inj Ranitidine					3500
Inj Streptomycin					10000
Inj Tavanic					60000
Inj Tinimax					5000
Inj Tramadol					5000
Inj Chloroquine					25000
Inj Tazogard					15000
Inj Buscopan					2500
Unj Erythropoietin 4000iu					12000
Inj Filgrastim					20000
Inj Chloramphenicol 1g					5000
Inj Calcium Gluconate					10000
Inj Vancomycin					15000
Inj Tandak					10000
Inj Anikacin					5500
Inj Morphine					15000
Inj Potassioun Chloride					35000
Inj Heparin					5000
Inj Vitamin K3					2500
Inj Arteether 					3000
Inj Adrenaline					2500
Inj Artesunate 60mg					7500
Inj Artesunate 120mg					10000
Inj Artesunate 180mg					14000
Inj Getzlox (Levofloxacin)					12000
Inj Aupam					5000
Inj Barrole (Rabeprazole)					8000
Inj Tetanus					2500
Inj Hydralazine					3000
Inj Labetolol					10000
Inj Fceftriaxone 1g					7000
Inj Avicef 1g					7000
Inj Ocexone 2g					20000
Inj Maxolon					2500
Inj Diclofenac					3500
Inj Cimetidine					3500
Inj Clexane					15000
Inj E-mal 150mg x 3Amps					15000
Inj E-Mal 75mg					15000
Inj Methyl Prednisolone					10000
Inj Depo (Triamcinolone) 40mg					12000
Inj Linezolide					35000
Inj Zopetum 45mg					15000
Inj Astymin					30000
Inj Meropenem 1g					20000
Inj Imipenem					17000
Inj Moxiget					30000
Inj Ceftriaxone & Sulbactam 1.5g					7000
Inj Vitamin B Complex					2500
Inj Hydrocortisone					2500
Inj Chlorpromazine					5000
Inj Chlorpheniramine					2500
Inj Ceftazidine					5000
Inj Ciprofloxacin					5000
Inj Dexamethasone					3000
Inj Deca-D (Nandrolone 25mg)					3500
Inj Diasepam					5000
Inj Frudemide					2500
Inj Flagyl					4000
Inj Gentamycin					3000
Inj Levofloxacin					5000
Inj Metoclp[ramide					2500
Inj Omeprazole					5000
Inj Ondansetron					3500
Inj Promethazine					2500
Inj Vitamin K1					2500
Inj Rocephin 1g					20000
Inj Clindamycin 300mg					5000
Inj Ketorolac					5000
Inj Fleming 1.2g					15000
Inj Fytobact 1g					5000
Inj Ralphec					7000
Inj Vitamin C 500mg					5500
Inj T-Acid					3500
Tab Linsinophil 100mg					700
Tab Isonaizide					1000
Tab Albendazole					1000
Tab Diclofenac 75gm					3500
Tab Diazepam 10mg					5000
Tab Flagyl 500mg					4000
Tab Gentamycine 80mg 					35000
Tab Omeprazole 40mg					5000
Infusion Paracetamol 1g					8500
Cap Vitamin A 2,00,000iu					1000
Gran Amino Guard					2000
Cap Tramadol 50mg					700
Tab Fansidar 					 
Tab Diclofenac 50mg					400
Tab Amoxiclad 1g					1500
Tab Levotixibe 750mg					1000
Tab Marcfix 325mg					1500
Tab Jalsatan 80mg					700
Tab Jalsatan 16mg					700
Tab Xarelto 10mg					2000
Tab Clindamycin 300mg					800
Tab Zinrat 500mg					2000
Tab Renede Plus					1000
Inj Tinimax 800mg					5000
Tab Pyrazinamide					1000
Inj Tramadol 100mg					5000
Tab Embaforg 5/160125					1000
Tab Cognitol					500
Cap Ginko Bilona					1000
Tab Arthrotec					800
Syr Ocefix 100mls					8000
Collomak sol					10000
PRICE_LIST;

$config = require dirname(__DIR__, 2) . '/config/app.php';
$database = $config['database'];

$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $database['name'] . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$departmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Pharmacy' LIMIT 1")->fetchColumn();
if ($departmentId <= 0) {
    throw new RuntimeException('Pharmacy department not found.');
}

$createdBy = (int)$pdo->query("SELECT id FROM users ORDER BY CASE WHEN username = 'walter' THEN 0 ELSE 1 END, id LIMIT 1")->fetchColumn();
if ($createdBy <= 0) {
    throw new RuntimeException('No user account exists to assign as creator.');
}

$upsert = $pdo->prepare(
    'INSERT INTO billable_items (
        item_code, item_name, item_type, department_id, description,
        unit_price, unit, is_active, created_by, created_at, updated_at
    ) VALUES (
        :item_code, :item_name, "Drug", :department_id, :description,
        :unit_price, :unit, 1, :created_by, NOW(), NOW()
    )
    ON DUPLICATE KEY UPDATE
        item_name = VALUES(item_name),
        item_type = VALUES(item_type),
        department_id = VALUES(department_id),
        description = VALUES(description),
        unit_price = VALUES(unit_price),
        unit = VALUES(unit),
        is_active = 1,
        updated_at = NOW()'
);

$priced = 0;
$skipped = [];
$packItems = [];

foreach (preg_split('/\R/', $raw) ?: [] as $line) {
    $line = trim((string)$line);
    if ($line === '') {
        continue;
    }

    if (!preg_match('/^(.*?)\s+((?:\d{1,3}(?:,\d{3})+|\d+))(?:\s*(Per\s+Pack|Per\s+pack))?\s*$/i', $line, $matches)) {
        $skipped[] = preg_replace('/\s+/', ' ', $line) ?? $line;
        continue;
    }

    $priced++;
    $itemName = trim(preg_replace('/\s+/', ' ', $matches[1]) ?? $matches[1]);
    $unitPrice = (float)str_replace(',', '', $matches[2]);
    $unit = isset($matches[3]) && trim((string)$matches[3]) !== '' ? 'Pack' : 'Unit';
    if ($unit === 'Pack') {
        $packItems[] = $itemName;
    }

    $itemCode = 'PHA-DRG-' . str_pad((string)$priced, 4, '0', STR_PAD_LEFT);
    $upsert->execute([
        ':item_code' => $itemCode,
        ':item_name' => $itemName,
        ':department_id' => $departmentId,
        ':description' => 'Pharmacy department drug price list item.',
        ':unit_price' => number_format($unitPrice, 2, '.', ''),
        ':unit' => $unit,
        ':created_by' => $createdBy,
    ]);
}

fwrite(STDOUT, 'Pharmacy priced billable rows processed: ' . $priced . PHP_EOL);
fwrite(STDOUT, 'Rows without usable price skipped: ' . count($skipped) . PHP_EOL);
if ($packItems !== []) {
    fwrite(STDOUT, 'Price-per-pack rows stored with unit Pack: ' . implode(', ', $packItems) . PHP_EOL);
}
if ($skipped !== []) {
    fwrite(STDOUT, 'Skipped rows: ' . implode('; ', $skipped) . PHP_EOL);
}
