<?php
namespace Elois\Model\Model;

use Elois\Bin\Core\ORM\ORM;
use Elois\Bin\Core\Helper\Helpers as H;
use \Elois\Bin\Core\Container\Container as C;
use \Elois\Bin\Core\ORM\EntityManager;
use Elois\Model\Entity\CommissionsCalcul;
use Exception;
use stdClass;

class ComissionsImports {

    public $fraisHono = 20;
    public $fraisResil = 30;
    public $freeResilBroker = [12363, 1487, 11569, 2698, 10674, 8743, 8474, 1117, 3839, 9206,1586, 13393, 13596, 2084, 13278,13147,12144,712,7491,1375,1393,7451,11007,11008,2645,3085,9411,8115,4472,2698,2930,2100,7753,9734,9171,11687,3277,3613,14444,14330,14534,9883,1165,1307,862,8032,3574,8115,3200,770, 1091, 1151, 1585, 3982, 4291, 11871, 12523, 12425, 13042, 12308, 13030, 2592, 3966, 4471, 13670, 2920, 3345, 3346, 14693, 13682, 13497, 14433, 4403, 10782, 11836, 2888,1384, 2345, 14670, 11265, 4074, 2729, 2948, 14223, 2821, 20143, 2301, 12731, 4202,1600, 1705, 1333, 9450, 1136, 3558, 1951, 11669, 13082, 12735, 8713, 3224, 3524, 7679, 8924, 10638, 3721, 1562, 286, 2450, 3714, 20168, 3721, 9901, 2686, 12714, 9327, 9535, 13067, 11870, 12143, 8753, 10668, 1669];

    public function removeCommissionsImport(\Elois\Model\Entity\CommissionsImportsLog &$log) {
        $em = new EntityManager;
        //global $debugSQL;
        $em->deleteCommissionsImportsByCilId($log->getCilId());
        // $em->delete($log);
        //  $em->persist($log);
        // die('nous passons ici');

    }

    public function removeCommissionsImportForever(\Elois\Model\Entity\CommissionsImportsForeverLog &$log) {
        $em = new EntityManager;
        //global $debugSQL;
        $em->deleteCommissionsImportsForeverByCiflId($log->getCiflId());
        // $em->delete($log);
        //  $em->persist($log);
        // die('nous passons ici');

    }

    public function checkErrors($month, $year){
        try {
            $pdo = ORM::getPDO();
            $query = 
                <<<SQL
                    SELECT
                        CI_contrat_ref,
                        CI_contrat_ref as CI_contrat_ref2,
                        cn.CN_id,
                        cn.CN_contrat_ref

                    FROM Commissions_Imports ci

                    /** Jointures **/
                    LEFT JOIN Contrats cn ON ( ci.CI_contrat_ref = cn.CN_contrat_ref AND (cn.CN_statut <> 'devis' OR cn.CN_statut IS NULL) )
                    LEFT JOIN Type_Contrats tc ON ( tc.TC_id = cn.TC_id )
                    LEFT JOIN Courtiers cr ON ( cr.CR_id = cn.CR_id )
                    LEFT JOIN Courtiers maitre ON ( maitre.CR_id = cr.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl1 ON ( maitreLvl1.CR_id = cr.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl2 ON ( maitreLvl2.CR_id = maitreLvl1.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl3 ON ( maitreLvl3.CR_id = maitreLvl2.CR_id_maitre )
                    LEFT JOIN Courtiers surcr ON ( surcr.CR_id = tc.TC_sur_comm_CR_id )
                    LEFT JOIN Contrats_Extranet ce ON ( ce.CE_id = tc.CE_id )
                    LEFT JOIN Clients cl ON ( cl.CL_id = cn.CL_id )
                    LEFT JOIN Paiement_Stripe ps ON ( ps.PS_id = cn.PS_id AND ps.PS_launch_date > '2021-07-14' )

                    WHERE CI_mois_annee=:monthYear AND CI_date_effet IS NOT NULL

                    UNION ALL

                    SELECT
                    CONCAT(ci.CIF_contrat_ref, "-", ci.CIF_taux) as CI_contrat_ref,
                    CIF_contrat_ref as CI_contrat_ref2,
                    cn.CN_id,
                    cn.CN_contrat_ref

                    FROM Commissions_Imports_Forever ci

                    /** Jointures **/
                    LEFT JOIN Contrats cn ON ( ci.CIF_contrat_ref = cn.CN_contrat_ref AND (cn.CN_statut <> 'devis' OR cn.CN_statut IS NULL) )
                    LEFT JOIN Type_Contrats tc ON ( tc.TC_id = cn.TC_id )
                    LEFT JOIN Courtiers cr ON ( cr.CR_id = cn.CR_id )
                    LEFT JOIN Courtiers maitre ON ( maitre.CR_id = cr.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl1 ON ( maitreLvl1.CR_id = cr.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl2 ON ( maitreLvl2.CR_id = maitreLvl1.CR_id_maitre )
                    LEFT JOIN Courtiers maitreLvl3 ON ( maitreLvl3.CR_id = maitreLvl2.CR_id_maitre )
                    LEFT JOIN Courtiers surcr ON ( surcr.CR_id = tc.TC_sur_comm_CR_id )
                    LEFT JOIN Contrats_Extranet ce ON ( ce.CE_id = tc.CE_id )
                    LEFT JOIN Clients cl ON ( cl.CL_id = cn.CL_id )
                    LEFT JOIN Paiement_Stripe ps ON ( ps.PS_id = cn.PS_id AND ps.PS_launch_date > '2021-07-14' )

                    WHERE CIF_mois_annee=:monthYear AND CIF_date_effet IS NOT NULL
SQL;// l'indentation de cette ligne (SQL;) est à maintenir ! (voir PHP 7.0 https://3v4l.org/DDin8#v7.0.33)

            $CIs = $pdo->prepare($query);
            $CIs->execute([':monthYear' => "$month/$year"]);
            $unfoundContrat = [];

            while ($CI = $CIs->fetch(\PDO::FETCH_ASSOC)) {
                if (empty($CI['CN_id'])) {
                    $unfoundContrat[$CI['CI_contrat_ref2']] = '';
                }
            }

            $hasError = !empty($unfoundContrat);
            
            return ['unfound'=>$unfoundContrat, 'hasError'=>$hasError ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function calcul($mois, $annee) {
        try {
        $em = new EntityManager;
        ini_set('precision', 5);
        set_time_limit (-1);

        $microtime = microtime(true);
        $pdo       = ORM::getPDO();
        //on prepart le tableau surcom qu'on va garder en statique, t'as vu
        $pdo->exec("DELETE FROM Commissions_Calcul WHERE CC_mois_annee='$mois/$annee'" );
        $rawSurCom = $pdo->query('SELECT comm.*,cr.CR_code_courtier,cr.CR_denomination FROM `Plus_Comm_Type_Contrats` comm JOIN Courtiers cr ON cr.CR_id = comm.plus_comm_CR_id')->fetchAll(\PDO::FETCH_ASSOC);
        $surComs   = [];
        $objectResponse = new stdClass();

        foreach ($rawSurCom as $r) {
            $surComs[$r["CR_id"]][$r['TC_version']]['CR_id']             = $r['plus_comm_CR_id'];
            $surComs[$r["CR_id"]][$r['TC_version']]['TC_version']        = $r['TC_version'];
            $surComs[$r["CR_id"]][$r['TC_version']]['PC_plus_comm_taux'] = $r['PC_plus_comm_taux'];
            $surComs[$r["CR_id"]][$r['TC_version']]['CR_code_courtier']  = $r['CR_code_courtier'];
            $surComs[$r["CR_id"]][$r['TC_version']]['CR_denomination']   = $r['CR_denomination'];
        }

        $dateCompare = \DateTime::createFromFormat('m/Y', "$mois/$annee");
        $query       = <<<SQL
                SELECT
                    ci.*,
                    CI_contrat_ref,
                    CI_contrat_ref as CI_contrat_ref2,
                    tc.TC_type_contrat,
                    tc.TC_sur_comm_CR_id,
                    cl.CL_nom,
                    cl.CL_prenom,
                    cn.CN_date_effet_souhaitee,
                    cn.CN_id,
                    cn.CN_contrat_ref,
                    cn.CN_statut,
                    cn.CN_statut_resil,
                    cn.CN_statut_resil_autre,
                    cn.CN_statut_date_resil,
                    tc.TC_taux_courtier_annees_suivantes,
                    tc.TC_taux_courtier_annee_1,
                    cr.CR_mandataire,
                    cr.CR_mandataire_taux,
                    cr.CR_bordereau_master,
                    cn.CN_validation_definitive,
                    cr.CR_id,
                    tc.TC_groupe,
                    ce.CE_nom,
                    tc.TC_type_contrat,
                    tc.TC_sur_comm_taux,
                    cl.CL_id,
                    cr.CR_id_maitre,
                    cr.CR_code_courtier,
                    cr.CR_denomination,
                    cr.CR_resil,
                    cr.CR_honoraire_gratuit,
                    tc.TC_groupe,
                    tc.TC_type_contrat,
                    tc.TC_version,
                    ce.CE_famille,
                    surcr.CR_id as sur_CR_id, surcr.CR_denomination as sur_CR_denomination,
                    surcr.CR_code_courtier as sur_CR_code_courtier,
                /** courtier maitre **/
                    maitre.CR_id as maitre_CR_id,
                    maitre.CR_denomination as maitre_CR_denomination,
                    maitre.CR_code_courtier as maitre_CR_code_courtier,
                    maitre.CR_resil as maitre_CR_resil,
                    maitre.CR_honoraire_gratuit as maitre_CR_honoraire_gratuit,
                    /** courtier maitre level 1 **/
                    maitreLvl1.CR_id as maitreLvl1_CR_id,
                    maitreLvl1.CR_denomination as maitreLvl1_CR_denomination,
                    maitreLvl1.CR_code_courtier as maitreLvl1_CR_code_courtier,
                    maitreLvl1.CR_resil as maitreLvl1_CR_resil,
                    maitreLvl1.CR_honoraire_gratuit as maitreLvl1_CR_honoraire_gratuit,
                    maitreLvl1.CR_bordereau_master as maitreLvl1_CR_bordereau_master,
                /** courtier maitre level 2 **/
                    maitreLvl2.CR_id as maitreLvl2_CR_id,
                    maitreLvl2.CR_denomination as maitreLvl2_CR_denomination,
                    maitreLvl2.CR_code_courtier as maitreLvl2_CR_code_courtier,
                    maitreLvl2.CR_resil as maitreLvl2_CR_resil,
                    maitreLvl2.CR_honoraire_gratuit as maitreLvl2_CR_honoraire_gratuit,
                    maitreLvl2.CR_bordereau_master as maitreLvl2_CR_bordereau_master,
                /** courtier maitre level 3 **/
                    maitreLvl3.CR_id as maitreLvl3_CR_id,
                    maitreLvl3.CR_denomination as maitreLvl3_CR_denomination,
                    maitreLvl3.CR_code_courtier as maitreLvl3_CR_code_courtier,
                    maitreLvl3.CR_resil as maitreLvl3_CR_resil,
                    maitreLvl3.CR_honoraire_gratuit as maitreLvl3_CR_honoraire_gratuit,
                    maitreLvl3.CR_bordereau_master as maitreLvl3_CR_bordereau_master,
                    ps.PS_montant,
                    ps.PS_brokerPaid_date,
                    ps.PS_statut,
                    ps.PS_id,
                    cn.CN_creation,
                    "" as source,
                    IFNULL( maitre.CR_code_courtier,cr.CR_code_courtier) as order_field,
                    IF( maitre.CR_code_courtier is null,1,2) as order_hierarchie
                    FROM Commissions_Imports ci


                /** MES JOINS **/
                    LEFT JOIN Contrats           cn      ON ( ci.CI_contrat_ref =  cn.CN_contrat_ref   AND  (cn.CN_statut <> 'devis' OR  cn.CN_statut IS NULL)      )
                    LEFT JOIN Type_Contrats      tc      ON ( tc.TC_id          =  cn.TC_id                                          )
                    LEFT JOIN Courtiers          cr      ON ( cr.CR_id          =  cn.CR_id                                          )
                    LEFT JOIN Courtiers          maitre  ON ( maitre.CR_id      =  cr.CR_id_maitre                                   )
                    LEFT JOIN Courtiers          maitreLvl1  ON ( maitreLvl1.CR_id      =  cr.CR_id_maitre                           )
                    LEFT JOIN Courtiers          maitreLvl2   ON ( maitreLvl2.CR_id      =  maitreLvl1.CR_id_maitre                  )
                    LEFT JOIN Courtiers          maitreLvl3   ON ( maitreLvl3.CR_id      =  maitreLvl2.CR_id_maitre                  )
                    LEFT JOIN Courtiers          surcr   ON ( surcr.CR_id       =  tc.TC_sur_comm_CR_id                              )
                    LEFT JOIN Contrats_Extranet  ce      ON ( ce.CE_id          =  tc.CE_id                                          )
                    LEFT JOIN Clients            cl      ON ( cl.CL_id          =  cn.CL_id                                          )
                    LEFT JOIN Paiement_Stripe    ps      ON ( ps.PS_id          =  cn.PS_id AND ps.PS_launch_date > '2021-07-14'                                         )

                    WHERE CI_mois_annee=:moisAnnee   AND CI_date_effet IS NOT NULL

                UNION ALL

                SELECT
                    ci.*,
                    CONCAT(ci.CIF_contrat_ref, "-", ci.CIF_taux) as CI_contrat_ref,
                    CIF_contrat_ref as CI_contrat_ref2,
                    tc.TC_type_contrat,
                    tc.TC_sur_comm_CR_id,
                    cl.CL_nom,
                    cl.CL_prenom,
                    cn.CN_date_effet_souhaitee,
                    cn.CN_id,
                    cn.CN_contrat_ref,
                    cn.CN_statut,
                    cn.CN_statut_resil,
                    cn.CN_statut_resil_autre,
                    cn.CN_statut_date_resil,
                    tc.TC_taux_courtier_annees_suivantes,
                    tc.TC_taux_courtier_annee_1,
                    cr.CR_mandataire,
                    cr.CR_mandataire_taux,
                    cr.CR_bordereau_master,
                    cn.CN_validation_definitive,
                    cr.CR_id,
                    tc.TC_groupe,
                    ce.CE_nom,
                    tc.TC_type_contrat,
                    tc.TC_sur_comm_taux,
                    cl.CL_id,
                    cr.CR_id_maitre,
                    cr.CR_code_courtier,
                    cr.CR_denomination,
                    cr.CR_resil,
                    cr.CR_honoraire_gratuit,
                    tc.TC_groupe,
                    tc.TC_type_contrat,
                    tc.TC_version,
                    ce.CE_famille,
                    surcr.CR_id as sur_CR_id, surcr.CR_denomination as sur_CR_denomination,
                    surcr.CR_code_courtier as sur_CR_code_courtier,
                    /** courtier maitre **/
                    maitre.CR_id as maitre_CR_id,
                    maitre.CR_denomination as maitre_CR_denomination,
                    maitre.CR_code_courtier as maitre_CR_code_courtier,
                    maitre.CR_resil as maitre_CR_resil,
                    maitre.CR_honoraire_gratuit as maitre_CR_honoraire_gratuit,
                    /** courtier maitre level 1 **/
                    maitreLvl1.CR_id as maitreLvl1_CR_id,
                    maitreLvl1.CR_denomination as maitreLvl1_CR_denomination,
                    maitreLvl1.CR_code_courtier as maitreLvl1_CR_code_courtier,
                    maitreLvl1.CR_resil as maitreLvl1_CR_resil,
                    maitreLvl1.CR_honoraire_gratuit as maitreLvl1_CR_honoraire_gratuit,
                    maitreLvl1.CR_bordereau_master as maitreLvl1_CR_bordereau_master,
                /** courtier maitre level 2 **/
                    maitreLvl2.CR_id as maitreLvl2_CR_id,
                    maitreLvl2.CR_denomination as maitreLvl2_CR_denomination,
                    maitreLvl2.CR_code_courtier as maitreLvl2_CR_code_courtier,
                    maitreLvl2.CR_resil as maitreLvl2_CR_resil,
                    maitreLvl2.CR_honoraire_gratuit as maitreLvl2_CR_honoraire_gratuit,
                    maitreLvl2.CR_bordereau_master as maitreLvl2_CR_bordereau_master,
                /** courtier maitre level 3 **/
                    maitreLvl3.CR_id as maitreLvl3_CR_id,
                    maitreLvl3.CR_denomination as maitreLvl3_CR_denomination,
                    maitreLvl3.CR_code_courtier as maitreLvl3_CR_code_courtier,
                    maitreLvl3.CR_resil as maitreLvl3_CR_resil,
                    maitreLvl3.CR_honoraire_gratuit as maitreLvl3_CR_honoraire_gratuit,
                    maitreLvl3.CR_bordereau_master as maitreLvl3_CR_bordereau_master,
                    ps.PS_montant,
                    ps.PS_brokerPaid_date,
                    ps.PS_statut,
                    ps.PS_id,
                    cn.CN_creation,
                    'FOREVER' as source,
                    IFNULL( maitre.CR_code_courtier,cr.CR_code_courtier) as order_field,
                    IF( maitre.CR_code_courtier is null,1,2) as order_hierarchie
                     FROM Commissions_Imports_Forever ci


                    LEFT JOIN Contrats           cn      ON ( ci.CIF_contrat_ref =  cn.CN_contrat_ref   AND  (cn.CN_statut <> 'devis' OR  cn.CN_statut IS NULL)      )
                    LEFT JOIN Type_Contrats      tc      ON ( tc.TC_id          =  cn.TC_id                                          )
                    LEFT JOIN Courtiers          cr      ON ( cr.CR_id          =  cn.CR_id                                          )
                    LEFT JOIN Courtiers          maitre  ON ( maitre.CR_id      =  cr.CR_id_maitre                                   )
                    LEFT JOIN Courtiers          maitreLvl1  ON ( maitreLvl1.CR_id      =  cr.CR_id_maitre                           )
                    LEFT JOIN Courtiers          maitreLvl2   ON ( maitreLvl2.CR_id      =  maitreLvl1.CR_id_maitre                  )
                    LEFT JOIN Courtiers          maitreLvl3   ON ( maitreLvl3.CR_id      =  maitreLvl2.CR_id_maitre                  )
                    LEFT JOIN Courtiers          surcr   ON ( surcr.CR_id       =  tc.TC_sur_comm_CR_id                              )
                    LEFT JOIN Contrats_Extranet  ce      ON ( ce.CE_id          =  tc.CE_id                                          )
                    LEFT JOIN Clients            cl      ON ( cl.CL_id          =  cn.CL_id                                          )
                    LEFT JOIN Paiement_Stripe    ps      ON ( ps.PS_id          =  cn.PS_id AND ps.PS_launch_date > '2021-07-14'                                         )
                    WHERE CIF_mois_annee=:moisAnnee   AND CIF_date_effet IS NOT NULL
                    ORDER BY order_field, order_hierarchie, CN_date_effet_souhaitee

SQL;

        $CIs         = $pdo->prepare($query);
        $CIs->execute([':moisAnnee' => "$mois/$annee"]);

        $i                = 0;
        $surcoTcComm      = [];
        $commForXml       = [];
        $surComm          = [];
        $commCalculLignes = [];
        $previousCnId     = 0;
        $resil_paid       = [];
        $hono_paid        = [];
        $unfound          = [];
        $ccResil          = [];
        $ccHono           = [];
        $comm;
        $cilId;

        while ($CI = $CIs->fetch(\PDO::FETCH_ASSOC)) {
            $cilId = $CI['CIL_id'];
            if (empty($CI['CN_id'])) {
                $unfound[$CI['CI_contrat_ref2']] = '';
                continue;
            }else{

                unset($unfound[$CI['CI_contrat_ref2']]);
            }

            $i++;
            $commCalcul = [];


            //STEP 1 : Interval date  +  choix du taux selon l'année 1 ou année suivantes
            $interval   = (new \DateTime($CI['CI_date_effet']))->diff($dateCompare);
            $anneeEcart = $interval->format('%R%y');
            $taux       = ($anneeEcart > 0) ? $CI['TC_taux_courtier_annees_suivantes']
                : $CI['TC_taux_courtier_annee_1'];
            //STEP 2 : courtier mandataire, calcul du nouveau taux
            $newTaux    = ($CI['CR_mandataire'] == 1) ? $taux - (int) $CI['CR_mandataire_taux'] : $taux;
            if($newTaux<0) $newTaux = 0 ;

            if ($CI['CI_epargne'] == 1) {// si on est dans le cas d'un contrat épargne le taux n'est pas set à ce stade et vient de l'import
                $newTaux = $CI['CI_taux']*100;
            }
            if($CI['source'] == 'FOREVER')
                $newTaux = $CI['CI_taux'];

            //STEP 3 : surco sur type contrat (ex : MI2R pour les acceo)
            if (!empty($CI['TC_sur_comm_CR_id'])) {
                //$SurCourtier = $em->getOneCourtiersByCrId($TC->getTcSurCommCrId());
                if(!isset($surcoCc[$CI['sur_CR_id']][$CI['TC_groupe']][$CI['CI_contrat_ref']])){
                    $surcoCc[$CI['sur_CR_id']][$CI['TC_groupe']][$CI['CI_contrat_ref']] = array(
                        'CR_id'           => $CI['sur_CR_id'],
                        'TC_Type_Contrat' => $CI['TC_type_contrat'],
                        'TC_contrat_ref'  => $CI['CI_contrat_ref2'],
                        'CL_nom'          => $CI['CL_nom'],

                        'CL_prenom'       => $CI['CL_prenom'],
                        'Date_Effet'      => $CI['CN_date_effet_souhaitee'],
                        'Taux'            => $CI['TC_sur_comm_taux'], // * 100,
                        'Montant_ht'      => $CI['CI_montant_ht'],
                        'Montant'         => (($newTaux * $CI['CI_montant_ht']) ) . '€',
                    );
                }else{
                    $surcoCc[$CI['sur_CR_id']][$CI['TC_groupe']][$CI['CI_contrat_ref']]['Montant_ht'] += $CI['CI_montant_ht'];
                }
                $surcoTcComm[$CI['sur_CR_id']] = array(
                    'CR_code_courtier' => $CI['sur_CR_id'],
                    'CR_denomination'  => $CI['sur_CR_denomination'],
                    'commissions'      => $surcoCc[$CI['sur_CR_id']]);
            }

            //STEP 4 : Calcul des comms pour les courtiers

            /* SI BORDEREAUX MAITRE COCHé, le courtier qui touche la commission est le maitre */

            if ($CI['maitreLvl2_CR_bordereau_master'] == 1 && $CI['maitreLvl1_CR_bordereau_master'] == 1 && $CI['CR_bordereau_master'] == 1) {
                $idDuCourtierQuiVaToucherLaCom           = $CI['maitreLvl3_CR_id'];
                $codeDuCourtierQuiVaToucherLaCom         = $CI['maitreLvl3_CR_code_courtier'];
                $denominationDuCourtierQuiVaToucherLaCom = $CI['maitreLvl3_CR_denomination'];
                $freeResil = $CI['maitreLvl3_CR_resil'];
                $freeHonos = $CI['maitreLvl3_CR_honoraire_gratuit'];

                // $crIdEsclave = $CI['maitreLvl2_CR_id'];
                // $CodeCourtierEsclave = $CI['maitreLvl2_CR_code_courtier'];
                // $DenominationCourtierEsclave = $CI['maitreLvl2_CR_denomination'];
                $crIdEsclave = $CI['CR_id'];
                $CodeCourtierEsclave = $CI['CR_code_courtier'];
                $DenominationCourtierEsclave = $CI['CR_denomination'];

                /* Sinon c'est le courtier courant (editeur du contrat) */
            }
            elseif ($CI['maitreLvl1_CR_bordereau_master'] == 1 && $CI['CR_bordereau_master'] == 1) {

                $idDuCourtierQuiVaToucherLaCom           = $CI['maitreLvl2_CR_id'];
                $codeDuCourtierQuiVaToucherLaCom         = $CI['maitreLvl2_CR_code_courtier'];
                $denominationDuCourtierQuiVaToucherLaCom = $CI['maitreLvl2_CR_denomination'];
                $freeResil = $CI['maitreLvl2_CR_resil'];
                $freeHonos = $CI['maitreLvl2_CR_honoraire_gratuit'];

                // $crIdEsclave = $CI['maitreLvl1_CR_id'];
                // $CodeCourtierEsclave = $CI['maitreLvl1_CR_code_courtier'];
                // $DenominationCourtierEsclave = $CI['maitreLvl1_CR_denomination'];
                $crIdEsclave = $CI['CR_id'];
                $CodeCourtierEsclave = $CI['CR_code_courtier'];
                $DenominationCourtierEsclave = $CI['CR_denomination'];

                /* Sinon c'est le courtier courant (editeur du contrat) */
            }
            elseif ($CI['CR_bordereau_master'] == 1) {
                $idDuCourtierQuiVaToucherLaCom           = $CI['maitreLvl1_CR_id'];
                $codeDuCourtierQuiVaToucherLaCom         = $CI['maitreLvl1_CR_code_courtier'];
                $denominationDuCourtierQuiVaToucherLaCom = $CI['maitreLvl1_CR_denomination'];
                $freeResil = $CI['maitreLvl1_CR_resil'];
                $freeHonos = $CI['maitreLvl1_CR_honoraire_gratuit'];

                $crIdEsclave = $CI['CR_id'];
                $CodeCourtierEsclave = $CI['CR_code_courtier'];
                $DenominationCourtierEsclave = $CI['CR_denomination'];

                /* Sinon c'est le courtier courant (editeur du contrat) */
            }
            else {
                $idDuCourtierQuiVaToucherLaCom           = $CI['CR_id'];
                $codeDuCourtierQuiVaToucherLaCom         = $CI['CR_code_courtier'];
                $denominationDuCourtierQuiVaToucherLaCom = $CI['CR_denomination'];
                $crIdEsclave                            = '';
                $CodeCourtierEsclave                            = '';
                $DenominationCourtierEsclave                            = '';
                $freeResil = $CI['CR_resil'];
                $freeHonos = $CI['CR_honoraire_gratuit'];

            }

            if ($CI['CN_validation_definitive'] != 1) {
                $contractEntity = $em->getOneContratsByCnId($CI['CN_id']);
                $contractEntity->setCnValidationDefinitive(1);
                $em->persist($contractEntity);
            }

            if(empty($idDuCourtierQuiVaToucherLaCom)) continue;


            $index = $CI['CR_id'];
            if (!isset($cc[$idDuCourtierQuiVaToucherLaCom][$index][$CI['TC_groupe']][$CI['CI_contrat_ref']])) {

                if($CI['CN_statut_resil'] == 'clos' && ($CI['CN_statut_resil_autre'] != 'paye'))
                {
                    $contractEntity = $em->getOneContratsByCnId($CI['CN_id']);
                    $contractEntity->setCnStatutResilAutre('paye');
                    $contractEntity->setCnStatutDateResil(date("$annee-$mois-d H:i:s"));
                    $em->persist($contractEntity);
                    $CI['CN_statut_resil_autre'] = 'paye';
                    $CI['CN_statut_date_resil'] = date("$annee-$mois-d H:i:s");

                }

                if($CI['PS_montant'] && $CI['PS_statut'] == 3){
                    $Ps = $em->getOnePaiementStripeByPsId($CI['PS_id']);
                    $Ps->setPsStatut(4);
                    $Ps->setPsBrokerPaidDate(date("$annee-$mois-d H:i:s"));
                    $em->persist($Ps);
                    $CI['PS_statut'] = 4;
                    $CI['PS_brokerPaid_date'] = date("$annee-$mois-d H:i:s");
                }

                $cc[$idDuCourtierQuiVaToucherLaCom][$index][$CI['TC_groupe']][$CI['CI_contrat_ref']]//ici !!!
                    = array(
                    'CR_id'           => $idDuCourtierQuiVaToucherLaCom,
                    'CR_code_courtier' => $codeDuCourtierQuiVaToucherLaCom,
                    'TC_Type_Contrat' => $CI['TC_type_contrat'],
                    'TC_contrat_ref'  => $CI['CI_contrat_ref2'],
                    'CL_nom'          => $CI['CL_nom'],
                    'CR_id_esclave'   => $crIdEsclave,

                    'Code_Courtier_esclave'   => $CodeCourtierEsclave,
                    'Denomination_esclave'   => $DenominationCourtierEsclave,
                    'CL_prenom'       => $CI['CL_prenom'],
                    'Date_Effet'      => $CI['CN_date_effet_souhaitee'],
                    'Taux'            => $newTaux,
                    'Montant_ht'      => $CI['CI_montant_ht'],
                    'Montant'         => ($newTaux/100 * $CI['CI_montant_ht']),
                    'STATUT_RESIL' => $CI['CN_statut_resil'],
                    'STATUT_RESIL_AUTRE' => $CI['CN_statut_resil_autre'],
                    'STATUT_DATE_RESIL' => $CI['CN_statut_date_resil'],
                    'Montant_honoraire' => $CI['PS_montant'],
                    'STATUT_honoraire' => $CI['PS_statut'],
                    'Paiement_date' => $CI['PS_brokerPaid_date'],
                    'CN_creation' => $CI['CN_creation'],
                    'RESIL_GRATUIT' => $freeResil,
                    'HONOS_GRATUIT' => $freeHonos
                );

                    // if($CI['CN_statut_resil'] == 'clos' && ($CI['CN_statut_resil_autre'] != 'paye' || ($CI['CN_statut_resil_autre'] == 'paye' && date('m/Y', strtotime($CI['CN_statut_date_resil'])) == "$mois/$annee")))
                                    // {
                                    //     $ccResil[$idDuCourtierQuiVaToucherLaCom][]  =  $cc[$idDuCourtierQuiVaToucherLaCom][$index][$CI['TC_groupe']][$CI['CI_contrat_ref']];
                                    // }

            }else{
                $cc[$idDuCourtierQuiVaToucherLaCom][$index][$CI['TC_groupe']][$CI['CI_contrat_ref']]['Montant_ht'] += $CI['CI_montant_ht'];
                $cc[$idDuCourtierQuiVaToucherLaCom][$index][$CI['TC_groupe']][$CI['CI_contrat_ref']]['Montant']    += (($newTaux/100) * $CI['CI_montant_ht']);

            }
            $comm[$idDuCourtierQuiVaToucherLaCom] = array(
                'CR_code_courtier' => $codeDuCourtierQuiVaToucherLaCom, //$lecourtier->getCrCodeCourtier(),
                'CR_denomination'  => $denominationDuCourtierQuiVaToucherLaCom, //$lecourtier->getCrDenomination(),
                'freeResil' => $freeResil,
                'freeHonos' => $freeHonos,
                'commissions'      => $cc[$idDuCourtierQuiVaToucherLaCom]); //$cc[$lecourtier->getCrId()]);
            // if(!isset($tab_resil[$idDuCourtierQuiVaToucherLaCom])){
            //     $tab_resil[$idDuCourtierQuiVaT]
            // }
            //

            if(!isset($commForXml[$idDuCourtierQuiVaToucherLaCom]))
                $commForXml[$idDuCourtierQuiVaToucherLaCom] = 0;

            $commForXml[$idDuCourtierQuiVaToucherLaCom] += (($newTaux/100) * $CI['CI_montant_ht']);

            if(!in_array($CI['CN_id'], $resil_paid)){
                if(($CI['CN_statut_resil'] == 'clos' && ($CI['CN_statut_resil_autre'] != 'paye' || ($CI['CN_statut_resil_autre'] == 'paye' && date('m/Y', strtotime($CI['CN_statut_date_resil'])) == "$mois/$annee"))))
                {
                    if($freeResil == 1)
                        $fraisResil = 0;
                    else
                        $fraisResil = $this->fraisResil;

                    $commForXml[$idDuCourtierQuiVaToucherLaCom] -= $fraisResil;

                    $resil_paid[] = $CI['CN_id'];
                }
            }

        if(!in_array($CI['CN_id'], $hono_paid)){
         if (!empty($CI['PS_montant']) && ($CI['PS_statut'] == 3 || ($CI['PS_statut'] == 4 and (date('m/Y', strtotime($CI['PS_brokerPaid_date'])) == "$mois/$annee")))){
             if(($CI['CN_statut_resil'] == 'clos' && ($CI['CN_statut_resil_autre'] != 'paye' || ($CI['CN_statut_resil_autre'] == 'paye' && date('m/Y', strtotime($CI['CN_statut_date_resil'])) == "$mois/$annee"))) || $freeHonos == 1)
                $frais = 0;
            else
                $frais = $this->fraisHono;
            $commForXml[$idDuCourtierQuiVaToucherLaCom] += ($CI['PS_montant'] - $frais);
            $hono_paid[] = $CI['CN_id'];
         }
     }

            $commCalcul         = [
                'CC_mois_annee' => $CI['CI_mois_annee'],
                'CR_id'         => $idDuCourtierQuiVaToucherLaCom,
                'CR_id_esclave'   => $crIdEsclave,
                'CN_id'         => $CI['CN_id'],
                'CIL_id'        => $CI['CIL_id'],
                'CIFL_id'       => isset($CI['CIFL_id'])?$CI['CIFL_id']:null,
                'CC_prime_ht'   => $CI['CI_montant_ht'],
                'CC_taux'       => $newTaux,
                'CC_montant'    => (($newTaux/100) * $CI['CI_montant_ht']),
                'CC_source'     => $CI['source'],
            ];
            $commCalculLignes[] = $commCalcul;


            //STEP 5 : calcul des surcom d'un courtier sur l'autre (par type contrat)
            /**
             * Entité Plus_Comm_Type_Contrats
             * recherche pour $lecourtier s'il a des courtier en surco selon le typecontrat
             * si oui, calcul
             */
//                $PlusCommTaux = $em->isEqual(['TC_id' => $TC->getTcId()])
//                        ->getOnePlusCommTypeContratsByCrId($lecourtier->getCrId());
//            if (isset($surComs[$idDuCourtierQuiVaToucherLaCom]))
//                    H::varDump($surComs);
//            if (isset($surComs[$idDuCourtierQuiVaToucherLaCom][$CI['TC_version']])) {
//            H::varDump($CI['TC_version']);

            if ($CI['maitreLvl2_CR_bordereau_master'] == 1 || $CI['maitreLvl1_CR_bordereau_master'] == 1 || $CI['CR_bordereau_master'] == 1) {
                $surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_id'] = $idDuCourtierQuiVaToucherLaCom;
                $surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_denomination'] = $codeDuCourtierQuiVaToucherLaCom;
                $surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_code_courtier'] = $denominationDuCourtierQuiVaToucherLaCom;
                // $surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_code_courtier'] = $denominationDuCourtierQuiVaToucherLaCom;
            }
//

            //if($idDuCourtierQuiVaToucherLaCom = '414') echo $CI['CE_famille'].'|'.$newTaux.'%<br/>';
            //if (isset($surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']])) {
            if (isset($surComs[$CI['CR_id']][$CI['CE_famille']])) {

                //$newTaux = $surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['PC_plus_comm_taux'];

                //$CI['CI_contrat_ref']
                if(!isset($surComs[$CI['CR_id']][$CI['CE_famille']]['PC_plus_comm_taux'])) {
                    $objectResponse->surcom = [];
                        $objectResponse->surcom = array_push(
                        $objectResponse->surcom, 
                        [
                            'CR_id'=>$CI['CR_id'],
                            'Ci_contrat_ref2'=>$CI['CI_contrat_ref2']
                        ]
                );
                }else{
                    $newTaux = $surComs[$CI['CR_id']][$CI['CE_famille']]['PC_plus_comm_taux'];
                }

                if (!isset($ccc[$surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_id']][$CI['TC_groupe']][$CI['CI_contrat_ref']])) {

                    $ccc[$surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_id']][$CI['TC_groupe']][$CI['CI_contrat_ref']]
                        = array(
                        //  'CR_id'                    => $idDuCourtierQuiVaToucherLaCom,
                        'souscom_CR_id'            => $CI['CR_id'],
                        'souscom_CR_denomination'  => $CI['CR_denomination'],
                        'souscom_CR_code_courtier' => $CI['CR_code_courtier'],
                        'TC_Type_Contrat'          => $CI['TC_type_contrat'],
                        'TC_contrat_ref'           => $CI['CI_contrat_ref2'],
                        'CL_nom'                   => $CI['CL_nom'],
                        'CL_prenom'                => $CI['CL_prenom'],
                        'Date_Effet'               => $CI['CN_date_effet_souhaitee'],
                        'Taux'                     => $newTaux, // * 100,
                        'Montant_ht'               => $CI['CI_montant_ht'],
                        'Montant'                  => ((($newTaux / 100) * $CI['CI_montant_ht'])) . '',
                    );
                }else{

                }

                $surComm[$surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_id']]
                    = array(
                    'CR_code_courtier' => $surComs[$CI['CR_id']][$CI['CE_famille']]['CR_code_courtier'],
                    'CR_denomination'  => $surComs[$CI['CR_id']][$CI['CE_famille']]['CR_denomination'],
                    'commissions'      => $ccc[$surComs[$idDuCourtierQuiVaToucherLaCom][$CI['CE_famille']]['CR_id']]);


                $commCalcul         = [
                    'CC_mois_annee' => $CI['CI_mois_annee'],
                    'CR_id'         => $idDuCourtierQuiVaToucherLaCom,
//                    'CR_id'         => $CI['CR_id'], //là pas sur !
                    'CR_id_esclave'   => $crIdEsclave,
                    'CN_id'         => $CI['CN_id'],
                    'CIL_id'        => $CI['CIL_id'],
                    'CIFL_id'       => isset($CI['CIFL_id'])?$CI['CIFL_id']:null,
                    'CC_prime_ht'   => $CI['CI_montant_ht'],
                    'CC_taux'       => $newTaux,
                    'CC_montant'    => ($newTaux * $CI['CI_montant_ht']),
                    'CC_source'     => $CI['source'],

                ];
                $commCalculLignes[] = $commCalcul;
            }
        }

        array_walk($commCalculLignes, function(&$v) {
            $v = implode(';', $v);
        });
        $csv             = implode("\n", $commCalculLignes);
        $csvPath         = get_include_path() . "/tmp/calculComCsv$mois-$annee.csv";
        file_put_contents(get_include_path() . "/tmp/calculComCsv$mois-$annee.csv", $csv);

        if(!IS_DEV){
            $conn_id       = ftp_connect('frontend.elois.lan');
            $ftp_user_name = "database";
            $ftp_user_pass = "Weeph2op";
            $login_result  = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

            if ((! $conn_id ) || (! $login_result )) {
                echo "FTP connection has failed!" ;
                exit;
            }

            $upload          = ftp_put($conn_id, "calculComCsv$mois-$annee.csv", $csvPath, FTP_BINARY);
            $filepathOnBdd   = "/var/www/html/localhost/www/intra.elois.fr/calculComCsv$mois-$annee.csv";
            $insertComCalcul = $pdo->prepare(
                " LOAD DATA INFILE  '{$filepathOnBdd}' INTO TABLE Commissions_Calcul  FIELDS TERMINATED BY ';' ( CC_mois_annee, CR_id, CR_id_esclave, CN_id, CIL_id , CIFL_id, CC_prime_ht, CC_taux ,CC_montant, CC_source);"
            );
            $insertComCalcul->execute();
        } else{
            $row = 0;
            if (($handle = fopen($csvPath, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                    $commissionImport = new CommissionsCalcul;
                    $commissionImport->setCcMoisAnnee($data[0]);
                    $commissionImport->setCrId($data[1]);
                    $commissionImport->setCrIdEsclave(empty($data[2]?0:$data[2]));
                    $commissionImport->setCnId($data[3]);
                    $commissionImport->setCILId($data[4]);
                    $commissionImport->setCiflId($data[5]);
                    $commissionImport->setCcPrimeHt($data[6]);
                    $commissionImport->setCcTaux($data[7]);
                    $commissionImport->setCcMontant($data[8]);
                    $commissionImport->setCcSource($data[9]);
                    $em->persist($commissionImport);
                }
                fclose($handle);

            }
            // echo('Impossible d\'importer un fichier en DEV');
        }

        if (!isset($comm)) {
            return $objectResponse->commCount = 0;
        }

        $inputFileCalculCom = file_put_contents(get_include_path() . "/tmp/calculCom$mois-$annee.php", '<?php return ' . var_export(['comm' => $comm, 'newsur' => $surComm, 'sur'  => $surComm, 'ccResil' => $ccResil, 'ccHono' => $ccHono], true) . '?>');
        if ($inputFileCalculCom == false) {
            throw new Exception('fichier php calculCOM n\'a pas été généré', 400);
        }

        $inputFileCalculXsml = file_put_contents(get_include_path() . "/tmp/calculXmlCom$mois-$annee.php", '<?php return ' . var_export($commForXml, true) . '?>');
        if ($inputFileCalculXsml == false) {
            throw new Exception('fichier php calculXML n\'a pas été généré', 400);
        }

        $objectResponse->commCount = count($comm);

        return $objectResponse;

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCommissionCalcul($mois, $annee) {
        /* @var \PDO $pdo */
        $pdo   = ORM::getPDO();
        $query = <<<SQL

                SELECT * FROM Comissions_calcul cc
                JOIN Courtiers cr on (cr.CR_id = cc.CR_id)
                WHERE cc.CC_mois_annee = :moisAnnee;

SQL;

        $req = $pdo->prepare($query);
        $req->execute([':moisAnnee' => "$mois/$annee"]);
        while ($req->fetch());
    }

    public function getCommissions($mois, $annee) {
        $cacheFilePath = (get_include_path() . "/tmp/calculComm$mois-$annee.php");
        if (file_exists($cacheFilePath)) {
            return (include $cacheFilePath);
        } else {
            return static::calcul($mois, $annee);
        }
    }

    public function deleteCommissionProcessed($mois, $annee) {

    }

    /**
     * @param int $idCourtier
     * @param int $mois
     * @param int $annee
     * @return string $path
     */
    static public function getPDFCommissionsCourtierURL($crId, $mois, $annee) {

        $c        = C::get();
        $filePath = "$crId/commissions$mois-$annee.pdf";
        $token    = \Elois\Controller\Extranet\Misc\SecureDownloadController::generateTokenCourtier($crId, $filePath);
        return "{$c['conf']['extranetURL']}/download/courtiers/pdf/$filePath?token=$token";
    }

    public function getPDFCommissionsCourtier($crId, $mois, $annee) {
        /**
         * lazygeneration (si existe on renvoie le path sinon on le genere)
         */
        $c        = C::get();
        $filePath = "$crId/commissions$mois-$annee.pdf";


        if (!is_dir(get_include_path() . $c['conf']['files']['path']['commissions'] . $crId)) {
            mkdir(get_include_path() . $c['conf']['files']['path']['commissions'] . $crId, 744);
        }


        $realPath = get_include_path() . $c['conf']['files']['path']['commissions'] . $filePath;
        @unlink($realPath);
        // <editor-fold defaultstate="collapsed" desc="Generation du PDF (conditionnel)">
        if (!file_exists($realPath)) {






//            $pdf = new \mikehaertl\wkhtmlto\Pdf;
            $toutesLesComm = include get_include_path() . "/tmp/calculCom$mois-$annee.php";
            $comms         = $toutesLesComm;
//
            $com = array();
           // H::varDump($comms);
//
            $leCourtier = (new \Elois\Bin\Core\ORM\EntityManager)->getOneCourtiersByCrId($crId);

            $comms['comm'] = [$crId => $comms['comm'][$crId]];

            $com['newsur'] = array();
            if(isset($comms['newsur']['$crId']))
                $com['newsur'] = [$crId => $comms['newsur'][$crId]];



            $moisSuiv = $mois+1;
            $anneeSuiv = $annee;
            if($moisSuiv>12){
                $moisSuiv = '01';
                $anneeSuiv = $anneeSuiv + 1;
            }
            $moisSuiv = str_pad($moisSuiv, 2 , '0', STR_PAD_LEFT);
//
            $lesolde = $leCourtier->getSoldeByMoisAnnee("$moisSuiv/$anneeSuiv")->getCrsSoldeDebutMois();
            // $affResil = false;
            if($annee.intval($mois) > 20212)
                $affResil = true;

            $test = ($crId == '301') ? 'Test'  :'';
            $pdfTable      = $c['twig']->render('Intranet/Commission/mailPdf'.$test.'.php.twig', [
                'comms'       => $comms['comm'],
                'SURCOTC'       => $com['newsur'],
                'courtier'    => $leCourtier,
                'soldeActuel'    => $lesolde,
                'affResil'    => $affResil,
                'fraisHono' => $this->fraisHono,
                'fraisResiliation' => $this->fraisResil,
                'freeResilBroker' => $this->freeResilBroker,
                'includepath' => get_include_path(),
                'moisAnneeText'   => (strftime('%B', mktime(0, 0, 0, $mois, 1, $annee))) . " $annee",
                'moisAnnee'   => "$mois/$annee",
                'moisSuivant' => (( strftime('%B %Y', strtotime($annee.'-'.$mois.'-01 +1 month'))))

            ]);

            $pdf = new \mikehaertl\wkhtmlto\Pdf;

            $footerHTML = $c['twig']->render('Intranet/Commission/mailFooter.php.twig', ['logoGenerali' => get_include_path() . 'web/medias/logos/logoGeneraliPdf.png']);

            $pdf->tmpDir = get_include_path() . 'tmp/';
            $pdf->setOptions(array(
                'margin-top'       => 10,
                'margin-right'     => 5,
                'margin-bottom'    => 13,
                'margin-left'      => 5,
                'user-style-sheet' => 'css/commissions-print.css',
                'header-html'      => '',
                'footer-html'      => $footerHTML
            ));
            //H::varDump($devisHTML);
            // echo $pdf->getCommand();
            // die();
            //    die($pdfTable);
            $pdf->addPage($pdfTable);
            $pdf->saveAs($realPath);
        }
        // </editor-fold>
        //H::varDump($pdf);
        return
            [
                'filePath' => $filePath,
                'realPath' => $realPath,
                'url'      => $this->getPDFCommissionsCourtierURL($crId, $mois, $annee)];
    }

    public function getCalculed($mois, $annee, $crId) {



        $em = new \Elois\Bin\Core\ORM\EntityManager;

        $query = <<<mysql


                SELECT * FROM Commissions_Calcul cc
                    JOIN   Contrats    cn  USING   (CN_id)
                    JOIN   Courtiers   cr  ON      (cr.CR_id=cc.CR_id)
                    JOIN   Clients     cl  USING   (CL_id)
                    JOIN   Clients     cl  USING   (CL_id)
                WHERE
                    cc.CR_id = '$crId'
                AND
                    cc.CC_mois_annee = '$mois/$annee'
mysql;




        $comm = [];
        foreach ($commCal as $com) {
            $comm[$com->getCrId()] = [
                'CR_code_courtier' => $com->getCourtiers()[0]->getCrCodeCourtier(),
                'CR_code_courtier' => $com->getCourtiers()[0]->getCrDenomination(),
                ''
            ];
            /*
             *   'comm' =>
              array (size=3)
              394 =>
              array (size=3)
              'CR_code_courtier' => string '00492' (length=5)
              'CR_denomination' => string 'SDB CONSEILS' (length=12)
              'commissions' =>
              array (size=1)
              'emprunteur' =>
              array (size=1)
              'Emprunteur Global' =>
              array (size=1)
              0 =>
              array (size=9)
              'CR_id' => string '394' (length=3)
              'TC_Type_Contrat' => string 'ACCEO EMP 20' (length=12)
              'TC_contrat_ref' => string '2453' (length=4)
              'CL_nom' => string 'PICAUD' (length=6)
              'CL_prenom' => string 'JOEL' (length=4)
              'Date_Effet' => string '2012-02-01' (length=10)
              'Taux' => string '0.2' (length=3)
              'Montant_ht' => string '54.19' (length=5)
              'Montant' => float 10.838
             */
        }
    }

    /**
     *
     * @param  $mois
     * @param $annee
     * @return array les mails concernés par le mois (avec crId en clef)
     */
    public function getListeEmailFinancier($mois, $annee) {
        $query = <<<sql
        SELECT cc.CR_id ,
               cc.CIL_id,
               cc.CIFL_id,
              if( LENGTH(cr.CR_email_financier) > 3, CR_email_financier,
                  if(LENGTH(cr.CR_email_gestion)> 3, CR_email_gestion,
	                    if(LENGTH(cr.CR_email_commercial)>3, CR_email_commercial,'NOMAIL')
                    )
                  ) as emails

        FROM Commissions_Calcul cc JOIN Courtiers cr USING (CR_id) WHERE  CC_mois_annee=:moisAnnee GROUP BY CR_id ;
sql;

        /* @var $pdo \PDO */
        $pdo   = ORM::getPDO();
        $mails = $pdo->prepare($query);

        $mails->execute([':moisAnnee' => "$mois/$annee"]);
        $return = [];
        foreach ($mails->fetchAll() as $m) {
            $return[$m['CR_id']]['adresses'] = explode(PHP_EOL, $m['emails']);
            $return[$m['CR_id']]['CIL_id'] = $m['CIL_id'];
            $return[$m['CR_id']]['CIFL_id'] = $m['CIFL_id'];
        }
        return $return;
    }




















    public function getXLSCommissionsCourtier($crId, $mois, $annee) {
        /**
         * lazygeneration (si existe on renvoie le path sinon on le genere)
         */
        $c        = C::get();
        $filePath = "$crId/commissions$mois-$annee.xls";

        if (!is_dir(get_include_path() . $c['conf']['files']['path']['commissions'] . $crId)) {
            mkdir(get_include_path() . $c['conf']['files']['path']['commissions'] . $crId, 744);
        }


        $realPath = get_include_path() . $c['conf']['files']['path']['commissions'] . $filePath;
        @unlink($realPath);
        // <editor-fold defaultstate="collapsed" desc="Generation du PDF (conditionnel)">
        if (!file_exists($realPath)) {






//            $pdf = new \mikehaertl\wkhtmlto\Pdf;
            $toutesLesComm = include get_include_path() . "/tmp/calculCom$mois-$annee.php";
            $comms         = $toutesLesComm;
            $comm = array();
//
//          //  H::varDump($comms);
//
            $comms['comm'] = [$crId => $comms['comm'][$crId]];
            $com['newsur'] = array();
            if(isset($comms['newsur']['$crId']))
                $com['newsur'] = [$crId => $comms['newsur'][$crId]];
//
//
//
//            die(utf8_encode( strftime('%B', mktime(0, 0, 0, ($mois%12 +1), 1, $annee)) ));
            $xlsTable      = $c['twig']->render('Intranet/Commission/commXls.php.twig', [
                'comms'       => $comms['comm'],
                'fraisHono' => $this->fraisHono,
                'fraisResiliation' => $this->fraisResil,
                'freeResilBroker' => $this->freeResilBroker,
                'courtier'    => (new \Elois\Bin\Core\ORM\EntityManager)->getOneCourtiersByCrId($crId),
                'SURCO'     => $com['newsur'],
                //     'SURCOTC'   => $comms['surtc'][$crId],
                'moisAnneeText'   => (strftime('%B', mktime(0, 0, 0, $mois, 1, $annee))) . " $annee",
                'moisAnnee'   => "$mois/$annee",
                'moisSuivant' => utf8_encode((( strftime('%B %Y', strtotime($annee.'-'.$mois.'-01 +1 month'))))),
            ]);
            //    die($xlsTable);
            file_put_contents($realPath, $xlsTable);

        }
        // </editor-fold>

        return
            [
                'filePath' => $filePath,
                'realPath' => $realPath,
                'url'      => $this->getXLSCommissionsCourtierURL($crId, $mois, $annee)];
    }














    /**
     * @param int $idCourtier
     * @param int $mois
     * @param int $annee
     * @return string $path
     */
    static public function getXLSCommissionsCourtierURL($crId, $mois, $annee) {

        $c        = C::get();
        $filePath = "$crId/commissions$mois-$annee.xls";
        $token    = \Elois\Controller\Extranet\Misc\SecureDownloadController::generateTokenCourtier($crId, $filePath);
        return "{$c['conf']['extranetURL']}/download/courtiers/xls/$filePath?token=$token";
    }










    public function cleanImport(&$log)
    {
        $pdo = ORM::getPDO();
        $em = new \Elois\Bin\Core\ORM\EntityManager;
        $em->getOneComissionsLogByCilId($CilId);
    }

    public function isMailSent($moisAnnee){

        return
            (new EntityManager)
                ->where('CIL_mail_sent=1')
                ->getOneCommissionsImportsLogByCilMoisAnnee($moisAnnee)
            !== FALSE;
    }
    public function isXMLSEPAGenerated($moisAnnee){
        return
            (new EntityManager)
                ->where('CIL_virement_fait=1')
                ->getOneCommissionsImportsLogByCilMoisAnnee($moisAnnee)
            !== FALSE;


    }

    public function isMailSentForever($moisAnnee){

        return
            (new EntityManager)
                ->where('CIFL_mail_sent=1')
                ->getOneCommissionsImportsForeverLogByCiflMoisAnnee($moisAnnee)
            !== FALSE;
    }
    public function isXMLSEPAGeneratedForever($moisAnnee){
        return
            (new EntityManager)
                ->where('CIFL_virement_fait=1')
                ->getOneCommissionsImportsForeverLogByCiflMoisAnnee($moisAnnee)
            !== FALSE;


    }


}
