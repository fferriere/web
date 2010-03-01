<?php

require_once 'Afup/AFUP_Personnes_Morales.php';
require_once 'Afup/AFUP_Personnes_Physiques.php';

define('AFUP_COTISATIONS_REGLEMENT_ESPECES' , 0);
define('AFUP_COTISATIONS_REGLEMENT_CHEQUE'  , 1);
define('AFUP_COTISATIONS_REGLEMENT_VIREMENT', 2);
define('AFUP_COTISATIONS_REGLEMENT_AUTRE'   , 3);
define('AFUP_COTISATIONS_REGLEMENT_ENLIGNE' , 4);

/**
 * Classe de gestion des personnes morales
 */
class AFUP_Cotisations
{
    /**
     * Instance de la couche d'abstraction à la base de données
     *
     * @var object
     * @access private
     */
    var $_bdd;

    /**
     * Constructeur.
     *
     * @param object $bdd Instance de la couche d'abstraction à la base de données
     * @access public
     * @return void
     */
    function AFUP_Cotisations(&$bdd)
    {
        $this->_bdd = $bdd;
    }

    /**
     * Renvoit la liste des cotisations concernant une personne
     *
     * @param int $type_personne Type de la personne (morale ou physique)
     * @param int $id_personne Identifiant de la personne
     * @param string $champs Champs à renvoyer
     * @param string $ordre Tri des enregistrements
     * @param bool $associatif Renvoyer un tableau associatif ?
     * @access public
     * @return array
     */
    function obtenirListe($type_personne, $id_personne, $champs = '*', $ordre = 'date_fin DESC', $associatif = false)
    {
        $requete = 'SELECT';
        $requete .= '  ' . $champs . ' ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  type_personne=' . $type_personne;
        $requete .= '  AND id_personne=' . $id_personne . ' ';
        $requete .= 'ORDER BY ' . $ordre;
        if ($associatif)
        {
            return $this->_bdd->obtenirAssociatif($requete);
        }
        else
        {
            return $this->_bdd->obtenirTous($requete);
        }
    }

    /**
     * Renvoit la cotisation demandée
     *
     * @param int $id Identifiant de la cotisation
     * @param string $champs Champs à renvoyer
     * @access public
     * @return array
     */
    function obtenir($id, $champs = '*')
    {
        $requete = 'SELECT';
        $requete .= '  ' . $champs . ' ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  id=' . $id;
        return $this->_bdd->obtenirEnregistrement($requete);
    }

    /**
     * Renvoit le numéro de la prochaine facture au format : {année}-{index depuis le début de l'année}
     *
     * @access private
     * @return string
     */
    function _genererNumeroFacture()
    {
        $requete  = 'SELECT';
        $requete .= "  MAX(CAST(SUBSTRING_INDEX(numero_facture, '-', -1) AS UNSIGNED)) + 1 ";
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  LEFT(numero_facture, 4)=' . $this->_bdd->echapper(date('Y'));
        $index = $this->_bdd->obtenirUn($requete);
        return date('Y') . '-' . (is_null($index) ? 1 : $index);
    }

    /**
     * Ajoute une cotisation
     *
     * @param int       $type_personne              Type de la personne (morale ou physique)
     * @param int       $id_personne                Identifiant de la personne
     * @param float     $montant                    Adresse de la personne
     * @param int       $type_reglement             Type de règlement (espèces, chèque, virement)
     * @param string    $informations_reglement     Informations concernant le règlement (numéro de chèque, de virement etc.)
     * @param int       $date_debut                 Date de début de la
     * cotisation
     * @param int       $date_fin                   Date de fin de la cotisation
     * @param string    $commentaires               Commentaires concernnant la cotisation
     * @access public
     * @return bool     Succès de l'ajout
     */
    function ajouter($type_personne, $id_personne, $montant, $type_reglement ,
                     $informations_reglement, $date_debut, $date_fin, $commentaires)
    {
        $requete = 'INSERT INTO ';
        $requete .= '  afup_cotisations (type_personne, id_personne, montant, type_reglement , informations_reglement,';
        $requete .= '                    date_debut, date_fin, numero_facture, commentaires) ';
        $requete .= 'VALUES (';
        $requete .= $type_personne                                        . ',';
        $requete .= $id_personne                                          . ',';
        $requete .= $montant                                              . ',';
        $requete .= $type_reglement                                       . ',';
        $requete .= $this->_bdd->echapper($informations_reglement)        . ',';
        $requete .= $date_debut                                           . ',';
        $requete .= $date_fin                                             . ',';
        $requete .= $this->_bdd->echapper($this->_genererNumeroFacture()) . ',';
        $requete .= $this->_bdd->echapper($commentaires)                  . ')';

        if ($this->_bdd->executer($requete) === false) {
            return false;
        }
        return true;
    }

    /**
     * Modifie une cotisation
     *
     * @param int       $id                         Identifiant de la cotisation à modifier
     * @param int       $type_personne              Type de la personne (morale ou physique)
     * @param int       $id_personne                Identifiant de la personne
     * @param float     $montant                    Adresse de la personne
     * @param int       $type_reglement             Type de règlement (espèces, chèque, virement)
     * @param string    $informations_reglement     Informations concernant le règlement (numéro de chèque, de virement etc.)
     * @param int       $date_debut                 Date de début de la
     * cotisation
     * @param int       $date_fin                   Date de fin de la cotisation
     * @param string    $commentaires               Commentaires concernnant la cotisation
     * @access public
     * @return bool Succès de la modification
     */
    function modifier($id, $type_personne, $id_personne, $montant, $type_reglement,
                      $informations_reglement, $date_debut, $date_fin, $commentaires)
    {
        $requete = 'UPDATE';
        $requete .= '  afup_cotisations ';
        $requete .= 'SET';
        $requete .= '  type_personne='          . $type_personne                                 . ',';
        $requete .= '  id_personne='            . $id_personne                                   . ',';
        $requete .= '  montant='                . $montant                                       . ',';
        $requete .= '  type_reglement='         . $type_reglement                                . ',';
        $requete .= '  informations_reglement=' . $this->_bdd->echapper($informations_reglement) . ',';
        $requete .= '  date_debut='             . $date_debut                                      . ',';
        $requete .= '  date_fin='               . $date_fin                                      . ',';
        $requete .= '  commentaires='           . $this->_bdd->echapper($commentaires)           . ' ';
        $requete .= 'WHERE';
        $requete .= '  id='                     . $id;
        if ($this->_bdd->executer($requete) === false) {
            return false;
        }
        return true;
    }

    function estDejaReglee($cmd)
    {
        $requete  = 'SELECT';
        $requete .= '  1 ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  informations_reglement=' . $this->_bdd->echapper($cmd);
        return $this->_bdd->obtenirUn($requete);
    }

	function notifierRegelementEnLigneAuTresorier($cmd, $total, $autorisation, $transaction)
	{
        require_once 'Afup/AFUP_Configuration.php';
        $configuration = $GLOBALS['AFUP_CONF'];

		list($ref, $date, $type_personne, $id_personne, $reste) = explode('-', $cmd, 5);

        require_once 'phpmailer/class.phpmailer.php';
        if (AFUP_PERSONNES_MORALES == $type_personne) {
            $personnes = new AFUP_Personnes_Morales($this->_bdd);
        } else {
            $personnes = new AFUP_Personnes_Physiques($this->_bdd);
        }
        $infos = $personnes->obtenir($id_personne, 'nom, prenom, email');

        $mail = new PHPMailer;
        $mail->AddAddress("tresorier@afup.org", "Trésorier AFUP");
        $mail->AddBCC("perrick@noparking.net", "Trésorier AFUP");

        $mail->From     = "toutenligne@afup.org";
        $mail->FromName = "ToutEnLigne AFUP";
        if ($configuration->obtenir('mails|serveur_smtp')) {
            $mail->Host     = $configuration->obtenir('mails|serveur_smtp');
            $mail->Mailer   = "smtp";
        } else {
            $mail->Mailer   = "mail";
        }

        $sujet  = "Paiement cotisation AFUP\n";
        $mail->Subject = $sujet;

        $corps  = "Bonjour, \n\n";
        $corps .= "Une cotisation annuelle AFUP a été réglée.\n\n";
        $corps .= "Personne : " . $infos['nom'] . " " . $infos['prenom'] . " (" . $infos['email'] . ")\n";
        $corps .= "URL : http://" . $_SERVER['SERVER_NAME'].$configuration->obtenir('web|path')."/pages/administration/index.php?page=cotisations&type_personne=" . $type_personne . "&id_personne=" . $id_personne . "\n";
        $corps .= "Commande : " . $cmd."\n";
        $corps .= "Total : " . $total."\n";
        $corps .= "Autorisation : " . $autorisation."\n";
        $corps .= "Transaction : " . $transaction."\n\n";
        $mail->Body = $corps;
        $ok = $mail->Send();
        if (false === $ok) {
            return false;
        }
	}

    function validerReglementEnLigne($cmd, $total, $autorisation, $transaction)
    {
		$reference = substr($cmd, 0, strlen($cmd) - 4);
		$verif = substr($cmd, strlen($cmd) - 3, strlen($cmd));

		if (substr(md5($reference), -3) == strtolower($verif) and !$this->estDejaReglee($cmd)) {
			list($ref, $date, $type_personne, $id_personne, $reste) = explode('-', $cmd, 5);
			$date_debut = mktime(0, 0, 0, substr($date, 2, 2), substr($date, 0, 2), substr($date, 4, 4));

			$cotisation = $this->obtenirDerniere($type_personne, $id_personne);
	        $date_fin_precedente = $cotisation['date_fin'];

            if ($date_fin_precedente > 0 and time() <= $date_fin_precedente) {
	            $date_debut = strtotime('+1day', $date_fin_precedente);
	        }

			$date_fin = strtotime('+1year', $date_debut);
			$result = $this->ajouter($type_personne,
									$id_personne,
									$total,
									AFUP_COTISATIONS_REGLEMENT_ENLIGNE,
									$cmd,
									$date_debut,
									$date_fin,
									"autorisation : ".$autorisation." / transacation : ".$transaction);
		} else {
			$result = false;
		}

		return $result;
    }

    /**
     * Supprime une cotisation
     *
     * @param int   $id     Identifiant de la cotisation à supprimer
     * @access public
     * @return bool Succès de la suppression
     */
    function supprimer($id)
    {
        $requete = 'DELETE FROM afup_cotisations WHERE id=' . $id;
        return $this->_bdd->executer($requete);
    }

    /**
     * Génère une facture au format PDF
     *
     * @param int       $id_cotisation  Identifiant de la cotisation
     * @param string    $chemin         Chemin du fichier PDF à générer. Si ce chemin est omi, le PDF est renvoyé au navigateur.
     * @access public
     * @return bool
     */
    function genererFacture($id_cotisation, $chemin = null)
    {
        $requete    = 'SELECT * FROM afup_cotisations WHERE id=' . $id_cotisation;
        $cotisation = $this->_bdd->obtenirEnregistrement($requete);

        $table = $cotisation['type_personne'] == AFUP_PERSONNES_MORALES ? 'afup_personnes_morales' : 'afup_personnes_physiques';
        $requete  = 'SELECT * FROM ' . $table . ' WHERE id=' . $cotisation['id_personne'];
        $personne = $this->_bdd->obtenirEnregistrement($requete);

        require_once 'Afup/AFUP_Configuration.php';
        $configuration = $GLOBALS['AFUP_CONF'];

        // Construction du PDF
        require_once 'Afup/AFUP_PDF_Facture.php';
        $pdf = new AFUP_PDF_Facture($configuration);
        $pdf->AddPage();

        // Haut de page [afup]
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->Cell(130, 5, 'AFUP');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 5, utf8_decode($configuration->obtenir('afup|raison_sociale')));
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(130, 5, utf8_decode('Association Française des Utilisateurs de PHP'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 5, utf8_decode($configuration->obtenir('afup|adresse')));
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(130, 5, 'http://www.afup.org');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 5, $configuration->obtenir('afup|code_postal') . ' ' . utf8_decode($configuration->obtenir('afup|ville')));
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(130, 35, 'SIRET : '. $configuration->obtenir('afup|siret'));
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(60, 5, 'Email : ' . $configuration->obtenir('afup|email'));

        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Cell(130, 5);
        $pdf->Cell(60, 5, 'Le ' . date('d/m/Y', $cotisation['date_debut']));

        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();

        // A l'attention du client [adresse]
        $pdf->SetFont('Arial', 'BU', 10);
        $pdf->Cell(130, 5, utf8_decode('Objet : Facture n°' . $cotisation['numero_facture']));
        $pdf->SetFont('Arial', '', 10);

        if ($cotisation['type_personne'] == AFUP_PERSONNES_MORALES) {
            $nom = $personne['raison_sociale'];
        } else {
            $nom = $personne['prenom'] . ' ' . $personne['nom'];
        }
        $pdf->Ln(10);
        $pdf->MultiCell(130, 5, utf8_decode($nom . "\n" . $personne['adresse'] . "\n" . $personne['code_postal'] . "\n" . $personne['ville']));

        $pdf->Ln(15);

        $pdf->MultiCell(180, 5, utf8_decode("Facture concernant votre adhésion à l'Association Française des Utilisateurs de PHP (AFUP)."));
        // Cadre
        $pdf->Ln(10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(50, 5, 'Code', 1, 0, 'L', 1);
        $pdf->Cell(100, 5, utf8_decode('Désignation'), 1, 0, 'L', 1);
        $pdf->Cell(40, 5, 'Prix', 1, 0, 'L', 1);

        $pdf->Ln();
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell(50, 5, 'ADH', 1);
        $pdf->Cell(100, 5, utf8_decode("Adhésion AFUP jusqu'au " . date('d/m/Y', $cotisation['date_fin'])), 1);
        $pdf->Cell(40, 5, utf8_decode($cotisation['montant'] . ' '), 1);

        $pdf->Ln(15);
        $pdf->Cell(10, 5, 'TVA non applicable - art. 293B du CGI');

		if (is_null($chemin)) {
            $pdf->Output('facture.pdf', 'D');
        } else {
            $pdf->Output($chemin, 'F');
        }
    }

    /**
     * Envoi par mail d'une facture au format PDF
     *
     * @param   int     $id_cotisation      Identifiant de la cotisation
     * @access public
     * @return bool Succès de l'envoi
     */
    function envoyerFacture($id_cotisation)
    {
        require_once 'Afup/AFUP_Configuration.php';
        $configuration = $GLOBALS['AFUP_CONF'];

        require_once 'phpmailer/class.phpmailer.php';
	    $personne = $this->obtenir($id_cotisation, 'type_personne, id_personne');

		if ($personne['type_personne'] == AFUP_PERSONNES_MORALES) {
	        $personnePhysique = new AFUP_Personnes_Morales($this->_bdd);
		} else {
	        $personnePhysique = new AFUP_Personnes_Physiques($this->_bdd);
		}
	    $contactPhysique = $personnePhysique->obtenir($personne['id_personne'], 'nom, prenom, email');

        $mail = new PHPMailer;
		$mail->AddAddress($contactPhysique['email'], $contactPhysique['nom']." ".$contactPhysique['prenom']);

		$mail->From     = $configuration->obtenir('mails|email_expediteur');
        $mail->FromName = $configuration->obtenir('mails|nom_expediteur');
		if ($configuration->obtenir('mails|serveur_smtp')) {
			$mail->Host     = $configuration->obtenir('mails|serveur_smtp');
			$mail->Mailer   = "smtp";
		} else {
			$mail->Mailer   = "mail";
		}

        $sujet  = "Facture AFUP\n";
        $mail->Subject = $sujet;

		$corps  = "Bonjour, \n\n";
        $corps .= "Veuillez trouver ci-joint la facture correspondant à votre adhésion à l'AFUP.\n";
        $corps .= "Nous restons à votre disposition pour toute demande complémentaire.\n\n";
        $corps .= "Le bureau\n\n";
        $corps .= $configuration->obtenir('afup|raison_sociale')."\n";
        $corps .= $configuration->obtenir('afup|adresse')."\n";
        $corps .= $configuration->obtenir('afup|code_postal')." ".$configuration->obtenir('afup|ville')."\n";

        $mail->Body = $corps;

        $chemin_facture = AFUP_CHEMIN_RACINE . 'cache/fact' . $id_cotisation . '.pdf';
        $this->genererFacture($id_cotisation, $chemin_facture);
        $mail->AddAttachment($chemin_facture, 'facture.pdf');
        $ok = $mail->Send();
        @unlink($chemin_facture);
        return $ok;
    }

    /**
     * Retourne la dernière cotisation d'une personne morale
     *
     * @param	int	$id_personne	Identifiant de la personne
     * @access	public
     * @return	array
     */
	function obtenirDerniere($type_personne, $id_personne)
	{
        $requete = 'SELECT';
        $requete .= '  * ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  type_personne=' . $type_personne . ' ';
        $requete .= '  AND id_personne=' . $id_personne . ' ';
        $requete .= 'ORDER BY';
        $requete .= '  date_fin DESC ';
        $requete .= 'LIMIT 0, 1 ';
        return $this->_bdd->obtenirEnregistrement($requete);
	}

    /**
     * Retourne la date de début d'une cotisation.
     *
     * Cette date est déterminée par la date de fin de la cotisation précédente
     * s'il y en a une ou alors sur la date du jour dans le cas contraire.
     *
     * @param	int	$type_personne	Identifiant du type de personne
     * @param	int	$id_personne	Identifiant de la personne
     * @access	public
     * @return	int					Timestamp de la date de la cotisation
     */
    function obtenirDateDebut($type_personne, $id_personne)
    {
        $requete = 'SELECT';
        $requete .= '  date_fin ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations ';
        $requete .= 'WHERE';
        $requete .= '  type_personne=' . $type_personne;
        $requete .= '  AND id_personne=' . $id_personne;
        $date_debut = $this->_bdd->obtenirUn($requete);

        if ($date_debut !== false) {
            return strtotime('+1day', $date_debut);
        } else {
            return time();
        }
    }

    /**
     * Renvoit la liste des cotisations concernant une personne
     *
     * @access public
     * @param	int	$type_personne	Type de personne concerné
     * @return array
     */
    function obtenirListeRelances($type_personne)
    {
        // On récupère la date de fin de la dernière cotisation de chacun
        $requete = 'SELECT';
        $requete .= '  cotisations.id_personne, MAX(cotisations.date_fin) AS date_fin ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations AS cotisations ';

        if (AFUP_PERSONNES_MORALES === $type_personne) {
            $requete .= 'INNER JOIN afup_personnes_morales AS personnes ON personnes.id=cotisations.id_personne ';
        } else {
            $requete .= 'INNER JOIN afup_personnes_physiques AS personnes ON personnes.id=cotisations.id_personne ';
        }

        $requete .= 'WHERE';
        $requete .= '  cotisations.type_personne=' . $type_personne;
        $requete .= '  AND personnes.etat=' . AFUP_DROITS_ETAT_ACTIF . ' ';

        if (AFUP_PERSONNES_PHYSIQUES === $type_personne) {
            $requete .= '  AND personnes.id_personne_morale = 0 ';
        }

        $requete .= 'GROUP BY ';
        $requete .= '  cotisations.id_personne ';
        $requete .= 'HAVING ';
        $requete .= '  MAX(cotisations.date_fin) < '.time();
        $resultat = $this->_bdd->obtenirTous($requete);

        // On récupère les relances
        $where = '';
        for ($i = 0, $taille = count($resultat); $i < $taille; $i++) {
            $where .= ' OR (cotisations.id_personne=' . $resultat[$i]['id_personne'];
            $where .= '     AND cotisations.date_fin=' . $resultat[$i]['date_fin'] . ')';
        }

        $requete  = 'SELECT';

        if (AFUP_PERSONNES_MORALES === $type_personne) {
            $requete .= '  personnes.raison_sociale AS nom,';
        } else {
            $requete .= '  CONCAT(personnes.nom, " ",  personnes.prenom) AS nom,';
        }

        $requete .= '  personnes.email, ';
        $requete .= '  cotisations.type_personne, ';
        $requete .= '  cotisations.id_personne,';
        $requete .= '  cotisations.date_fin,';
        $requete .= '  IF(cotisations.nombre_relances IS NULL, 0, cotisations.nombre_relances) AS nombre_relances, ';
        $requete .= '  cotisations.date_derniere_relance ';
        $requete .= 'FROM';
        $requete .= '  afup_cotisations AS cotisations ';

        if (AFUP_PERSONNES_MORALES === $type_personne) {
            $requete .= 'JOIN afup_personnes_morales AS personnes ON personnes.id=cotisations.id_personne ';
        } else {
            $requete .= 'JOIN afup_personnes_physiques AS personnes ON personnes.id=cotisations.id_personne ';
        }

        $requete .= 'WHERE';
        $requete .= '  type_personne=' . $type_personne;
        $requete .= '  AND (0' . $where . ') ';
        $requete .= 'ORDER BY';
        $requete .= '  date_fin';

        return $this->_bdd->obtenirTous($requete);
    }

    /**
     * Relance une personne afin qu'elle renouvelle sa cotisation.
     *
     * @access public
     * @param	int	$type_personne	Type de personne concerné
     * @param	int $id_personne 	Identifiant de la personne concernée
     * @return bool
     */
    function relancer($type_personne, $id_personne)
    {
        require_once 'Afup/AFUP_Configuration.php';
        $configuration = $GLOBALS['AFUP_CONF'];

        require_once 'phpmailer/class.phpmailer.php';
        if (AFUP_PERSONNES_MORALES == $type_personne) {
            $personnes = new AFUP_Personnes_Morales($this->_bdd);
        } else {
            $personnes = new AFUP_Personnes_Physiques($this->_bdd);
        }
        $infos = $personnes->obtenir($id_personne, 'nom, prenom, email');

        $mail = new PHPMailer;
        $mail->AddAddress($infos['email'], $infos['nom'] . ' ' . $infos['prenom']);
        $mail->AddBCC("tresorier@afup.org", "Trésorier AFUP");

        $mail->From     = "tresorier@afup.org";
        $mail->FromName = "Trésorier AFUP";
        if ($configuration->obtenir('mails|serveur_smtp')) {
            $mail->Host     = $configuration->obtenir('mails|serveur_smtp');
            $mail->Mailer   = "smtp";
        } else {
            $mail->Mailer   = "mail";
        }

        $sujet  = "Relance cotisation AFUP\n";
        $mail->Subject = $sujet;

        $montant = AFUP_PERSONNES_MORALES == $type_personne ? 50 : 20;

        $corps  = "Bonjour, \n\n";
        $corps .= "Votre cotisation annuelle à l'AFUP est arrivée à son terme.\n\n";
        $corps .= "Afin de la renouveller, il vous suffit d'envoyer un chèque de " . $montant;
        $corps .= " euros libellé à l'ordre de l'AFUP ainsi que votre identité à l'adresse suivante :\n\n";
        $corps .= "AFUP\n";
        $corps .= "Villeneuve Christophe \n";
        $corps .= "BAT C1 - Appt 37\n";
        $corps .= "Résidence Les Millepertuis\n";
        $corps .= "91940 LES ULIS\n\n";
		//$corps .= "AFUP chez Anaska\n";
        //$corps .= "39 avenue du Raincy\n";
        //$corps .= "93250 Villemomble\n\n";
        $corps .= "Vous pouvez aussi la renvoueller directement :\n\n";
        $corps .= "* En ligne via l'espace d'administration:\n";
        $corps .= "  http://www.afup.org/pages/administration/\n\n";
        $corps .= "* Par virement bancaire en contactant le trésorier tresorier@afup.org:\n";
        $corps .= "Cordialement\n\n";
        $corps .= "Le trésorier";
        $mail->Body = $corps;
        $ok = $mail->Send();
        if (false === $ok) {
            return false;
        }

        $requete  = 'UPDATE';
        $requete .= '  afup_cotisations ';
        $requete .= 'SET';
        $requete .= '  nombre_relances=IF(nombre_relances IS NULL, 1, nombre_relances+1),';
        $requete .= '  date_derniere_relance=' . time() . ' ';
        $requete .= 'WHERE';
        $requete .= '  type_personne=' . $type_personne;
        $requete .= '  AND id_personne=' . $id_personne;
        return $this->_bdd->executer($requete);
    }
}

?>