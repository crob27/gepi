<?php
/**
 * Administration du trombinoscope
* $Id: trombinoscopes_admin.php 8586 2011-11-01 17:41:09Z mleygnac $
*
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Christian Chapel
 *
 * This file is part of GEPI.
 *
 * GEPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GEPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GEPI; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * @package Trombinoscope
 */


/*
 * Paramétrage du trombinoscope
 *
 * @param $_POST['activer'] activation/désactivation
 * @param $_POST['num_aid_trombinoscopes']
 * @param $_POST['activer_personnels']
 * @param $_POST['activer_redimensionne']
 * @param $_POST['activer_rotation']
 * @param $_POST['l_max_aff_trombinoscopes']
 * @param $_POST['h_max_imp_trombinoscopes']
 * @param $_POST['l_max_imp_trombinoscopes']
 * @param $_POST['h_max_imp_trombinoscopes']
 * @param $_POST['nb_col_imp_trombinoscopes']
 * @param $_POST['l_resize_trombinoscopes']
 * @param $_POST['h_resize_trombinoscopes']
 * @param $_POST['sousrub']
 * @param $_POST['supprime']
 * @param $_POST['is_posted']
 *
 * @return $accessibilite
 * @return $titre_page
 * @return $niveau_arbo
 * @return $gepiPathJava
 * @return $msg
 * @return $repertoire
 * @return $post_reussi
 *
 */

$accessibilite="y";
$titre_page = "Gestion du module trombinoscope";
$niveau_arbo = 1;
$gepiPathJava="./..";
$post_reussi=FALSE;


// Initialisations files
require_once("../lib/initialisations.inc.php");
require_once("../lib/share-csrf.inc.php");

function purge_dossier_photos($type_utilisateurs) {

	// $type_utilisateurs : eleves ou personnels
	global $repertoire_photos,$nb_photos_supp,$nb_erreurs;

	// $tab_identifiants : tableau des login ou elenoet présents dans la base
	$tab_identifiants=array(); $pt=0;
	// pour les élèves on cherchera parmi les fichiers elenoet.jpg
	if ($type_utilisateurs=="eleves")
		{
		$r_sql="SELECT `elenoet` FROM `eleves`";
		$R_identifiants=mysql_query($r_sql);
		if ($R_identifiants)
			{
			while ($pt<mysql_num_rows($R_identifiants))
				{
				$identifiant=mysql_result($R_identifiants,$pt++);
				$tab_identifiants[]=$identifiant;
				}
			}
		}
	// pour les personnels (et pour les élèves en multisite) on cherchera parmi les fichiers login.jpg
	$r_sql="SELECT `login` FROM `".($type_utilisateurs=="personnels"?"utilisateurs":"eleves")."`";
	$R_identifiants=mysql_query($r_sql);
	if ($R_identifiants)
		{

		while ($pt<mysql_num_rows($R_identifiants))
			{
			$identifiant=mysql_result($R_identifiants,$pt++);
			if ($type_utilisateurs=="personnels") $identifiant=md5(mb_strtolower($identifiant));
			$tab_identifiants[]=$identifiant;
			}
		}

	// $tab_identifiants_inactifs : tableau des login ou elenoet des comptes inactifs présents dans la base
	$tab_identifiants_inactifs=array();
	if (isset($_POST['cpts_inactifs']) && $_POST['cpts_inactifs']=="oui")
		{
		if ($type_utilisateurs=="eleves")
			$r_sql="SELECT `utilisateurs`.`login`,`eleves`.`elenoet` FROM `utilisateurs`,`eleves` WHERE (`statut`='eleve' AND `etat`='inactif' AND `utilisateurs`.`login`=`eleves`.`login`)";
			else
			$r_sql="SELECT `utilisateurs`.`login` FROM `utilisateurs` WHERE `etat`='inactif'";
		$R_inactifs=mysql_query($r_sql);
		if ($R_inactifs)
			{
			$pt=0;
			while ($pt<mysql_num_rows($R_inactifs))
				{
				// dans tous les cas (élèves ou personnels) on cherchera parmi les fichiers login.jpg
				$identifiant=mysql_result($R_inactifs,$pt,'login');
				if ($type_utilisateurs=="personnels") $identifiant=md5(mb_strtolower($identifiant));
				$tab_identifiants_inactifs[]=$identifiant;
				// dans le cas des élèves on cherchera également parmi les fichiers elenoet.jpg
				if ($type_utilisateurs=="eleves")
					{
					$identifiant=mysql_result($R_inactifs,$pt,'elenoet');
					$tab_identifiants_inactifs[]=$identifiant;
					}
				$pt++;
				}
			}
		}

	// on supprime les photos dont le nom ne se trouve pas dans $tab_identifiants
	// ou se trouve dans $tab_identifiants_inactifs
	$R_dossier_photos=opendir($repertoire_photos."/".$type_utilisateurs);
	while ($photo = readdir($R_dossier_photos))
		{
		if (is_file($repertoire_photos."/".$type_utilisateurs."/".$photo) && $photo!="index.html")
			{
			$nom_photo=pathinfo($repertoire_photos."/".$type_utilisateurs."/".$photo,PATHINFO_FILENAME);
			// en principe on ne trouve que des fichiers JPEG dans le dossier
			// et on en profite pour normaliser l'extension
			@rename($repertoire_photos."/".$type_utilisateurs."/".$photo,$repertoire_photos."/".$type_utilisateurs."/".$nom_photo.".jpg");
			if (!in_array($nom_photo,$tab_identifiants) || in_array($nom_photo,$tab_identifiants_inactifs))
				if (@unlink($repertoire_photos."/".$type_utilisateurs."/".$nom_photo.".jpg")) $nb_photos_supp++; else $nb_erreurs++;
			}
		}
}

function aplanir_tree($chemin,$destination) {
// déplace tous les fichiers du dossier $chemin dans le dossier $destination
// ! si deux fichiers de même nom se trouvent dans $chemin un seul sera déplacé
	$erreurs="";
    if ($chemin[strlen($chemin)-1]!="/") $chemin.= "/";
    if ($destination[strlen($destination)-1]!="/") $destination.= "/";
    if (is_dir($chemin)) {
		$dossier = opendir($chemin);
		while ($fichier = readdir($dossier)) {
			if ($fichier!="." && $fichier!="..") {
				$chemin_fichier=$chemin.$fichier;
				if (is_dir($chemin_fichier)) aplanir_tree($chemin_fichier,$destination);
					else 
						{
						if (!@copy($chemin_fichier,$destination."/".$fichier)) $erreurs.="Impossible de copier le fichier ".$chemin_fichier." vers ".$destination.".<br/>";
						if ($chemin_fichier!=$destination.$fichier)
							if (!@unlink($chemin_fichier)) $erreurs.="Impossible de supprimer le fichier ".$chemin_fichier.".<br/>";
						}
			}
		}
		closedir($dossier);
    }
	return $erreurs;
}


function del_tree($chemin) {
	// supprime le dossier ou le fichier $chemin
	$erreurs="";
    if ($chemin[strlen($chemin)-1] != "/") $chemin.= "/";
    if (is_dir($chemin)) {
		$dossier = opendir($chemin);
		while ($fichier = readdir($dossier)) {
			if ($fichier != "." && $fichier != "..") {
				$chemin_fichier = $chemin . $fichier;
				if (is_dir($chemin_fichier)) del_tree($chemin_fichier);
					else if (!@unlink($chemin_fichier)) $erreurs.="Impossible de supprimer le fichier ".$chemin_fichier.".<br/>";
			}
		}
		closedir($dossier);
		if (!@rmdir($chemin)) $erreurs.="Impossible de supprimer le dossier ".$chemin.".<br/>";
    }
	else if (!@unlink($chemin)) $erreurs.="Impossible de supprimer le fichier".$chemin.".<br/>";
	return $erreurs;
}


function copie_temp_vers_photos(&$nb_photos,$dossier_a_traiter,$type_a_traiter,$test_folder=false)
// $dossier_a_traiter : 'eleves' ou 'personnels'
// $type_a_traiter :  : 'élève' ou 'personnel'
{
	global $repertoire_photos,$dir_temp,$msg_nb_trts,$msg,$avertissement;
	$folder = $dir_temp."/photos/".$dossier_a_traiter."/";
	if($test_folder && !file_exists($folder)) {
		$avertissement.="Votre ZIP ne contient pas l'arborescence /photos/".$dossier_a_traiter." :</b><br/><span style='font-variant:normal; font-size: smaller;'>Si vous souhaitiez restaurer des photos des ".$type_a_traiter."s, vous devriez avoir<br/>dans votre ZIP les photos des ".$type_a_traiter."s dans un sous-dossier photos/".$dossier_a_traiter."/</span><br/>\n";
	}
	else {
		$nb_photos=0;
		$dossier = opendir($folder);
		while ($Fichier = readdir($dossier)) {
			if ($Fichier != "index.html" && $Fichier != "." && $Fichier != ".." && ((preg_match('/\.jpg/i', $Fichier))||(preg_match('/\.jpeg/i', $Fichier)))) {
				$Fichier_dest=pathinfo($Fichier,PATHINFO_FILENAME).".jpg";
				$source=$folder.$Fichier;
				$dest=$repertoire_photos.$dossier_a_traiter."/".$Fichier_dest;
				if (isset ($_POST["ecraser"]) && ($_POST["ecraser"]="yes")) {
					@copy($source, $dest);
					$nb_photos++;
				} else {
					if (!is_file($dest)) {
						@copy($source, $dest);
						$nb_photos++;
					}
				}



			}
		}
		if($nb_photos>0) {$msg_nb_trts.=$nb_photos." photo(s) ".$type_a_traiter."(s) transférée(s).<br/>\n";}
		closedir($dossier);
	}
}


// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}
// Check access
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

/******************************************************************
 *    Enregistrement des variables passées en $_POST si besoin
 ******************************************************************/
$msg = '';
if(isset($_POST['is_posted'])) {
	check_token();

	if (isset($_POST['num_aid_trombinoscopes'])) {
		if ($_POST['num_aid_trombinoscopes']!='') {
			if (!saveSetting("num_aid_trombinoscopes", $_POST['num_aid_trombinoscopes']))
					$msg = "Erreur lors de l'enregistrement du paramètre num_aid_trombinoscopes !";
		} else {
			$del_num_aid_trombinoscopes = mysql_query("delete from setting where NAME='num_aid_trombinoscopes'");
			$gepiSettings['num_aid_trombinoscopes']="";
		}
	}
	if (isset($_POST['activer'])) {
		if (!saveSetting("active_module_trombinoscopes", $_POST['activer']))
				$msg = "Erreur lors de l'enregistrement du paramètre activation/désactivation !";
		if (!cree_repertoire_multisite())
		$msg = "Erreur lors de la création du répertoire photos de l'établissement !";
	}
	
	if (isset($_POST['activer_personnels'])) {
		if (!saveSetting("active_module_trombino_pers", $_POST['activer_personnels']))
				$msg = "Erreur lors de l'enregistrement du paramètre activation/désactivation du trombinoscope des personnels !";
	}
	
	if (isset($_POST['activer_redimensionne'])) {
		if (!saveSetting("active_module_trombinoscopes_rd", $_POST['activer_redimensionne']))
				$msg = "Erreur lors de l'enregistrement du paramètre de redimenssionement des photos !";
	}
	if (isset($_POST['activer_rotation'])) {
		if (!saveSetting("active_module_trombinoscopes_rt", $_POST['activer_rotation']))
				$msg = "Erreur lors de l'enregistrement du paramètre rotation des photos !";
	}
	if (isset($_POST['l_max_aff_trombinoscopes'])) {
		if (!saveSetting("l_max_aff_trombinoscopes", $_POST['l_max_aff_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre largeur maximum !";
	}
	if (isset($_POST['h_max_aff_trombinoscopes'])) {
		if (!saveSetting("h_max_aff_trombinoscopes", $_POST['h_max_aff_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre hauteur maximum !";
	}
	if (isset($_POST['l_max_imp_trombinoscopes'])) {
		if (!saveSetting("l_max_imp_trombinoscopes", $_POST['l_max_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre largeur maximum !";
	}
	if (isset($_POST['h_max_imp_trombinoscopes'])) {
		if (!saveSetting("h_max_imp_trombinoscopes", $_POST['h_max_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre hauteur maximum !";
	}
	
	if (isset($_POST['nb_col_imp_trombinoscopes'])) {
		if (!saveSetting("nb_col_imp_trombinoscopes", $_POST['nb_col_imp_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du nombre de colonnes sur les trombinos imprimés !";
	}
	
	if (isset($_POST['l_resize_trombinoscopes'])) {
		if (!saveSetting("l_resize_trombinoscopes", $_POST['l_resize_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre l_resize_trombinoscopes !";
	}
	if (isset($_POST['h_resize_trombinoscopes'])) {
		if (!saveSetting("h_resize_trombinoscopes", $_POST['h_resize_trombinoscopes']))
				$msg = "Erreur lors de l'enregistrement du paramètre h_resize_trombinoscopes !";
	}
}

if (isset($_POST['is_posted']) and ($msg=='')) {
  $msg = "Les modifications ont été enregistrées !";
  $post_reussi=TRUE;
}

// Suppression de photos
	if(isset($_POST['sup_pers']) && $_POST['sup_pers']=="oui"){
		// suppression des photos du personnel
		if (!efface_photos("personnels"))
		$msg.="Erreur lors de la suppression des photos du personnel";
	}
	if (isset($_POST['supp_eleve']) && $_POST['supp_eleve']=="oui"){
		// suppression des photos des élèves
		if (!efface_photos("eleves"))
		$msg.="Erreur lors de la suppression des photos des élèves";
	}

// Affichage du personnel sans photo
	if(isset ($_POST['voirPerso']) && $_POST['voirPerso']=="yes"){
		if (!recherche_personnel_sans_photo()){
		$msg .= "Erreur lors de la sélection de professeur(s) sans photo";
		}else{
			$personnel_sans_photo=recherche_personnel_sans_photo();
			$msg.="liste des professeurs sans photo en bas de page <br/>";
			$post_reussi=TRUE;
			}
	}

// Affichage des élèves sans photo
	if (isset ($_POST['voirEleve']) && $_POST['voirEleve']=="yes"){
	if (!recherche_eleves_sans_photo()){
		$msg .= "Erreur lors de la sélection des élèves sans photo";
	}else{
		$eleves_sans_photo=recherche_eleves_sans_photo();
		$msg.="liste des élèves sans photo en bas de page";
		$post_reussi=TRUE;
		}
	}


// Purge du dossier photos
	$msg="";
	if (isset($_POST['purge_dossier_photos']) && $_POST['purge_dossier_photos']=="oui")
		{
		if (cree_zip_archive("photos")==TRUE)
			{

			$repertoire_photos=""; $msg_multisite="";
			if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite']=='y')
				// On récupère le RNE de l'établissement
				if (!$repertoire_photos=$_COOKIE['RNE'])

					$msg_multisite.="Multisite : erreur lors de la récupération du dossier photos de l'établissement.<br/>";


					if ($msg_multisite=="")
				{
				if ($repertoire_photos!="") $repertoire_photos.="/";
				$repertoire_photos="../photos/".$repertoire_photos;


				$nb_photos_supp=0; $nb_erreurs=0;
				// purge du dossier photos/eleves
				purge_dossier_photos("eleves");
				// purge du dossier photos/personnels
				purge_dossier_photos("personnels");

				if ($nb_photos_supp>0)
					if ($nb_photos_supp>1) $msg=$nb_photos_supp." photos ont été suprimées.<br/>";
						else $msg="Une photo a été suprimée.<br/>";
					else $msg="Aucune photo n'a été supprimée.<br/>";
				$post_reussi=TRUE;
				if ($nb_erreurs>0)
					{
					if ($nb_erreurs>1) $msg.=$nb_erreurs." photos n'ont pu être supprimées.<br/>";
						else $msg.="Une photo n'a pu être supprimée.<br/>";
					$post_reussi=FALSE;
					}
				} else $msg=$msg_multisite.$msg;
			}
		else $msg.="Erreur lors de la création de la sauvegarde.<br/>";
		}

// Liste des données élève
if (isset($_GET['liste_eleves']) and ($_GET['liste_eleves']=='oui'))  {
	check_token();

	/*
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=eleves_".getSettingValue("gepiYear").".csv");
	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Transfer-Encoding: base64");
	// pb de download avec IE
	if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
	{
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	} 
		else {header('Pragma: no-cache');
	}
	*/
	$csv="";
	$csv.="\"classe\",\"nom\",\"prénom\",\"prénom nom\",\"login\",\"elenoet\"\n";
	$r_sql="SELECT `eleves`.`nom`,`eleves`.`prenom`,`eleves`.`login`,`eleves`.`elenoet`,`classes`.`nom_complet` FROM `eleves`,`j_eleves_classes`,`classes` WHERE (`eleves`.`login`=`j_eleves_classes`.`login` AND `j_eleves_classes`.`id_classe`=`classes`.`id`) GROUP BY `login` ORDER BY `nom_complet`,`nom`,`prenom`";
	$R_eleves=mysql_query($r_sql);
	if ($R_eleves) {
		while ($un_eleve=mysql_fetch_assoc($R_eleves)) {
			$csv.="\"".$un_eleve['nom_complet']."\",\"".$un_eleve['nom']."\",\"".$un_eleve['prenom']."\",\"".$un_eleve['prenom']." ".$un_eleve['nom']."\",\"".$un_eleve['login']."\",\"".$un_eleve['elenoet']."\"\n";
		}
	}

	$nom_fic="eleves_".getSettingValue("gepiYear").".csv";
	send_file_download_headers('text/x-csv',$nom_fic);
	//echo $csv;
	echo echo_csv_encoded($csv);
	die();
}
	
// Chargement des photos élèves
if (isset($_POST['action']) and ($_POST['action']=='upload_photos_eleves'))  {
	check_token();
	$msg="";
	// Le téléchargement s'est-il bien passé ?
	$sav_file = isset($_FILES["nom_du_fichier"]) ? $_FILES["nom_du_fichier"] : NULL;
	if ($sav_file) {
		// c'est dans $dir_temp que le travail se fera
		$dir_temp="../temp/trombinoscopes";
		if ($multisite=='y' && isset($_COOKIE['RNE'])) $dir_temp."_".$_COOKIE['RNE'];
		if (is_file($dir_temp) && !@unlink($dir_temp)) $msg.="Impossible de supprimer ".$dir_temp.".<br/>\n";
			else if (!file_exists($dir_temp)) 
				if (!@mkdir($dir_temp,0700,true)) $msg.="Impossible de créer ".$dir_temp."..<br/>\n";
		if ($msg=="") {
			// astuce : pour rester compatible avec le script de restauration
			// on crée l'arborescence /photos/eleves
			$dir_temp_photos_eleves=$dir_temp."/photos/eleves";
			if (!file_exists($dir_temp_photos_eleves)) 
				if (!@mkdir($dir_temp_photos_eleves,0700,true)) $msg.="Impossible de créer ".$dir_temp_photos_eleves.".<br/>\n"; ;
			if ($msg=="") {
				// copie du fichier ZIP dans $dir_temp
				$reponse=telecharge_fichier($sav_file,$dir_temp_photos_eleves,"zip",'application/zip application/octet-stream application/x-zip-compressed');
				if ($reponse!="ok") {
					$msg.=$reponse;
				} else {
					// dézipage du fichier
					$reponse=dezip_PclZip_fichier($dir_temp_photos_eleves."/".$sav_file['name'],$dir_temp_photos_eleves."/",1);
					if ($reponse!="ok") {
						$msg.=$reponse;
					} else {
						//suppression du fichier .zip
						if (!@unlink ($dir_temp_photos_eleves."/".$_FILES["nom_du_fichier"]['name'])) {
							$msg .= "Erreur lors de la suppression de ".$dir_temp."/".$_FILES["nom_du_fichier"]."<br/>\n";
						}
					// quelque soit la structure du fichier .zip on déplace les photos dans $dir_temp_photos_eleves
					aplanir_tree($dir_temp_photos_eleves,$dir_temp_photos_eleves);

					// on renomme éventuellement les photos
					if (file_exists($dir_temp_photos_eleves."/correspondances.csv"))
						if (($fichier_csv=fopen($dir_temp_photos_eleves."/correspondances.csv","r"))!==FALSE)
							{
							while (($une_ligne=fgetcsv($fichier_csv,1000,","))!==FALSE) 
								if (count($une_ligne)==2) 
									rename($dir_temp_photos_eleves."/".$une_ligne[0],$dir_temp_photos_eleves."/".$une_ligne[1].".jpg");
							fclose($fichier_csv);
							}

					$repertoire_photos=""; $msg_multisite="";
					if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite']=='y')
						// On récupère le RNE de l'établissement
						if (!$repertoire_photos=$_COOKIE['RNE'])
							{
							$msg_multisite.="Multisite : erreur lors de la récupération du dossier photos de l'établissement.<br/>";
							}

					if ($msg_multisite=="")
						{
						if ($repertoire_photos!="") $repertoire_photos.="/";
						$repertoire_photos="../photos/".$repertoire_photos;
						$msg_nb_trts=""; // nb de fichiers traités
						// copie des fichiers vers /photos
						copie_temp_vers_photos($nb_photos_eleves,'eleves','élève');
						if ($msg_nb_trts=="") $msg_nb_trts="Aucune photo n'a été transférée.<br/>\n";
						if ($msg==""){
							$msg= $msg_nb_trts;
							$post_reussi=TRUE;
							} else $msg=$msg_nb_trts.$msg;
						} else $msg= $msg.$msg_multisite;
					}
				}
			}
		}
	// quoiqu'il se soit passé on supprime le dossier ../temp/trombinoscopes
	del_tree("../temp/trombinoscopes");
	}
}

// Restauration d'une sauvegarde
if (isset($_POST['action']) and ($_POST['action'] == 'upload'))  {
	check_token();
	$msg="";
	// Le téléchargement s'est-il bien passé ?
	$sav_file = isset($_FILES["nom_du_fichier"]) ? $_FILES["nom_du_fichier"] : NULL;
	if ($sav_file) {
		// c'est dans $dir_temp que le travail se fera
		$dir_temp ="../temp/trombinoscopes";
		if ($multisite=='y' && isset($_COOKIE['RNE'])) $dir_temp."_".$_COOKIE['RNE'];
		if (is_file($dir_temp) && !@unlink($dir_temp)) $msg.="Impossible de supprimer ".$dir_temp."<br/>\n";
			else if (!file_exists($dir_temp)) 
				if (!@mkdir($dir_temp,0700,true)) $msg.="Impossible de créer ".$dir_temp."<br/>\n";
		if ($msg=="") {
			//  copie du fichier ZIP dans $dir_temp
			$reponse=telecharge_fichier($sav_file,$dir_temp,"zip",'application/zip application/octet-stream application/x-zip-compressed');
			if ($reponse!="ok") {
				$msg.=$reponse;
			} else {
				// dézipage du fichier
				$reponse=dezip_PclZip_fichier($dir_temp."/".$sav_file['name'],$dir_temp,1);
				if ($reponse!="ok") {
					$msg .= $reponse;
				} else {
					//suppression du fichier .zip
					if (!@unlink ($dir_temp."/".$_FILES["nom_du_fichier"]['name'])) {
						$msg .= "Erreur lors de la suppression de ".$dir_temp."/".$_FILES["nom_du_fichier"]."<br/>\n";
					}

					$repertoire_photos=""; $msg_multisite="";
					if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite']=='y')
						// On récupère le RNE de l'établissement
						if (!$repertoire_photos=$_COOKIE['RNE'])
							{
							$msg_multisite.="Multisite : erreur lors de la récupération du dossier photos de l'établissement.<br/>";
							}

					if ($msg_multisite=="")
						{
						if ($repertoire_photos!="") $repertoire_photos.="/";
						$repertoire_photos="../photos/".$repertoire_photos;
						// copie des fichiers vers /photos
						$msg_nb_trts=""; // nb de fichiers traités
						$avertissement=""; // si l'arborescence est incomplète
						//Elèves
						copie_temp_vers_photos($nb_photos_eleves,'eleves','élève',true);
						//Personnels
						copie_temp_vers_photos($nb_photos_personnels,'personnels','personnel',true);
						if ($msg_nb_trts=="") $msg_nb_trts="Aucune photo n'a été transférée.<br/>\n";
						if ($msg==""){
							$msg= $msg_nb_trts.$avertissement;
							$post_reussi=TRUE;
							} else $msg= $msg_nb_trts.$avertissement.$msg;
						} else $msg= $avertissement.$msg.$msg_multisite;
					}
				}
			}
	// quoiqu'il se soit passé on supprime le dossier ../temp/trombinoscopes
	del_tree("../temp/trombinoscopes");
		}
}



// header
//$titre_page = "Gestion du module trombinoscope";


// En multisite, on ajoute le répertoire RNE
if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
	  // On récupère le RNE de l'établissement
  $repertoire=$_COOKIE['RNE']."/";
}else{
  $repertoire="";
}

/****************************************************************
                     HAUT DE PAGE
****************************************************************/

// ====== Inclusion des balises head et du bandeau =====
include_once("../lib/header_template.inc");

if (!suivi_ariane($_SERVER['PHP_SELF'],$titre_page))
		echo "erreur lors de la création du fil d'ariane";
/****************************************************************
			FIN HAUT DE PAGE
****************************************************************/
//debug_var();
if (getSettingValue("GepiAccesModifMaPhotoEleve")=='yes') {

  $req_trombino = mysql_query("select indice_aid, nom from aid_config order by nom");
  $nb_aid = mysql_num_rows($req_trombino);
  $i = 0;
  for($i = 0;$i < $nb_aid;$i++){
	  $aid_trouve[$i]["indice"]= mysql_result($req_trombino,$i,'indice_aid');
	  $aid_trouve[$i]["nom"]= mysql_result($req_trombino,$i,'nom');
	  if (getSettingValue("num_aid_trombinoscopes")==$aid_trouve[$i]["indice"]){
		$aid_trouve[$i]["selected"]= TRUE;
		echo getSettingValue("num_aid_trombinoscopes")." : ".$aid_trouve[$i]["indice"];
	  }else {
		$aid_trouve[$i]["selected"]= FALSE;

	  }
  }
}


/*
 * TODO : 
 * <?php if ( $sousrub === 've' ) {
 * }
 *
 * if ( $sousrub === 'vp' ) {
 * }
 *
 * if ( $sousrub === 'de' ) {
 * }
 *
 * if ( $sousrub === 'dp' ) {
 * }
 *
 * if ( $sousrub === 'deok' ) {
 * }
 *
 * if ( $sousrub === 'dpok' ) {
 * }
 */

/*require_once("../lib/header.inc");
?>

<p class='bold'><a href="../accueil_modules.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a></p>

<h2>Configuration générale</h2>
<i>La désactivation du module trombinoscope n'entraîne aucune suppression des données. Lorsque le module est désactivé, il n'y a pas d'accès au module.</i>
<br/>
<form action="trombinoscopes_admin.php" name="form1" method="post">
<p><strong>Elèves&nbsp;:</strong></p>
<blockquote>
<input type="radio" name="activer" id='activer_y' value="y" <?php if (getSettingValue("active_module_trombinoscopes")=='y') echo " checked='checked'"; ?>  />
<label for='activer_y' style='cursor:pointer'>&nbsp;Activer le module trombinoscope</label><br/>
<input type="radio" name="activer" id='activer_n' value="n" <?php
	if (getSettingValue("active_module_trombinoscopes")!='y'){echo " checked='checked'";}
?>  /><label for='activer_n' style='cursor:pointer'>&nbsp;Désactiver le module trombinoscope</label>
<input type="hidden" name="is_posted" value="1" />
</blockquote>

<p><strong>Personnels&nbsp;:</strong></p>
<blockquote>
<input type="radio" name="activer_personnels" id='activer_personnels_y' value="y" <?php if (getSettingValue("active_module_trombino_pers")=='y') echo " checked='checked'"; ?>  /><label for='activer_personnels_y' style='cursor:pointer'>&nbsp;Activer le module trombinoscope des personnels</label><br/>
<input type="radio" name="activer_personnels" id='activer_personnels_n' value="n" <?php
	if (getSettingValue("active_module_trombino_pers")!='y'){echo " checked='checked'";}
?>  /><label for='activer_personnels_n' style='cursor:pointer'>&nbsp;Désactiver le module trombinoscope des personnels</label>
</blockquote>

<br/>

<h2>Configuration d'affichage et de stockage</h2>
&nbsp;&nbsp;&nbsp;&nbsp;<i>Les valeurs ci-dessous vous servent au paramétrage des valeurs maxi des largeurs et des hauteurs.</i><br/>
<span style="font-weight: bold;">Pour l'écran</span><br/>
&nbsp;&nbsp;&nbsp;&nbsp;largeur maxi <input name="l_max_aff_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_max_aff_trombinoscopes"); ?>" />&nbsp;
hauteur maxi&nbsp;<input name="h_max_aff_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_max_aff_trombinoscopes"); ?>" />
<br/><span style="font-weight: bold;">Pour l'impression</span><br/>
&nbsp;&nbsp;&nbsp;&nbsp;largeur maxi <input name="l_max_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_max_imp_trombinoscopes"); ?>" />&nbsp;
hauteur maxi&nbsp;<input name="h_max_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_max_imp_trombinoscopes"); ?>" />&nbsp;Nombre de colonnes&nbsp;<input name="nb_col_imp_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("nb_col_imp_trombinoscopes"); ?>" />

<br/><span style="font-weight: bold;">Pour le stockage sur le serveur</span><br/>
&nbsp;&nbsp;&nbsp;&nbsp;largeur <input name="l_resize_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("l_resize_trombinoscopes"); ?>" />&nbsp;
hauteur &nbsp;<input name="h_resize_trombinoscopes" size="3" maxlength="3" value="<?php echo getSettingValue("h_resize_trombinoscopes"); ?>" />

<br/>
<h2>Configuration du redimensionnement des photos</h2>
<i>La désactivation du redimensionnement des photos n'entraîne aucune suppression des données. Lorsque le système de redimensionnement est désactivé, les photos transferées sur le site ne seront pas réduites en <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?>.</i>
<br/><br/>
<input type="radio" name="activer_redimensionne" id="activer_redimensionne_y" value="y" <?php if (getSettingValue("active_module_trombinoscopes_rd")=='y') echo " checked='checked'"; ?> /><label for='activer_redimensionne_y' style='cursor:pointer'>&nbsp;Activer le redimensionnement des photos en <?php echo getSettingValue("l_resize_trombinoscopes");?>x<?php echo getSettingValue("h_resize_trombinoscopes");?></label><br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Remarque</b> attention GD doit être actif sur le serveur de GEPI pour utiliser le redimensionnement.<br/>
<input type="radio" name="activer_redimensionne" id="activer_redimensionne_n" value="n" <?php if (getSettingValue("active_module_trombinoscopes_rd")=='n') echo " checked='checked'"; ?> /><label for='activer_redimensionne_n' style='cursor:pointer'>&nbsp;Désactiver le redimensionnement des photos</label>
<ul><li>Rotation de l'image : <input name="activer_rotation" value="" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='') { ?>checked='checked'<?php } ?> /> 0°
<input name="activer_rotation" value="90" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='90') { ?>checked='checked'<?php } ?> /> 90°
<input name="activer_rotation" value="180" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='180') { ?>checked='checked'<?php } ?> /> 180°
<input name="activer_rotation" value="270" type="radio" <?php if (getSettingValue("active_module_trombinoscopes_rt")=='270') { ?>checked='checked'<?php } ?> /> 270° &nbsp;Sélectionner une valeur si vous désirez une rotation de la photo originale</li>
</ul>

<h2>Gestion de l'accès des élèves</h2>
Dans la page "Gestion générale"->"Droits d'accès", vous avez la possibilité de donner à <b>tous les élèves</b> le droit d'envoyer/modifier lui-même sa photo dans l'interface "Gérer mon compte".
<br/>
<b>Si cette option est activée</b>, vous pouvez, ci-dessous, gérer plus finement quels élèves ont le droit d'envoyer/modifier leur photo.
<br/><b>Marche à suivre :</b>
<ul>
<li>Créez une "catégorie d'AID" ayant par exemple pour intitulé "trombinoscope".</li>
<li>Configurez l'affichage de cette catégorie d'AID de sorte que :
<br/>- L'AID n'apparaîsse pas dans le bulletin officiel,
<br/>- L'AID n'apparaîsse pas dans le bulletin simplifié.
<br/>Les autres paramètres n'ont pas d'importance.</li>
<li>Dans la "Liste des aid de la catégorie", ajoutez une ou plusieurs AIDs.</li>
<li>Ci-dessous, sélectionner dans la liste des catégories d'AIDs, celle portant le nom que vous avez donné ci-dessus.
<i>(cette liste n'appararaît pas si vous n'avez pas donné la possibilité à tous les élèves d'envoyer/modifier leur photo dans "Gestion générale"->"Droits d'accès")</i>.
</li>
<li>Tous les élèves inscrits dans une des AIDs de la catégorie sus-nommée pourront alors envoyer/modifier leur photo (<em>à l'exception des élèves sans numéro Sconet ou "elenoet"</em>).</li>
</ul>

<?php
if (getSettingValue("GepiAccesModifMaPhotoEleve")=='yes') {
    $req_trombino = mysql_query("select indice_aid, nom from aid_config order by nom");
    $nb_aid = mysql_num_rows($req_trombino);
    ?>
    <b>Nom de la cat&eacute;gorie d'AID permettant de g&eacute;rer l'acc&egrave;s des &eacute;l&egrave;ves : </b><select name="num_aid_trombinoscopes" size="1">
    <option value="">(aucune)</option>
    <?php
    $i = 0;
    while($i < $nb_aid){
        $indice_aid = mysql_result($req_trombino,$i,'indice_aid');
        $aid_nom = mysql_result($req_trombino,$i,'nom');
        $i++;
        echo "<option value='".$indice_aid."' ";
        if (getSettingValue("num_aid_trombinoscopes")==$indice_aid) echo " selected='selected'";
        echo ">".$aid_nom."</option>";
    }
    ?>
    </select><br/>
    <b>Remarque&nbsp;:</b> Si "aucune" AID n'est définie, <b>tous les élèves</b> peuvent envoyer/modifier leur photo (<em>sauf ceux sans elenoet</em>).
    <br/>
<?php
}
?>

<input type="hidden" name="is_posted" value="1" />
<div class="center"><input type="submit" value="Enregistrer" style="font-variant: small-caps;" /></div>
</form>

<a name="gestion_fichiers"></a>
<h2>Gestion des fichiers</h2>
<ul>
<li>Suppression
 <ul>
  <?php //if( file_exists('../photos/personnels/') ) { ?>
  <?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
  <li><a href="trombinoscopes_admin.php?sousrub=dp#validation">Vider le dossier photos des personnels</a></li>
  <?php //} if( file_exists('../photos/eleves/') ) {?>
  <?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
  <li><a href="trombinoscopes_admin.php?sousrub=de#validation">Vider le dossier photos des élèves</a></li>
  <?php } ?>
 </ul>
</li>
<li>Gestion
 <ul>
  <?php //if( file_exists('../photos/personnels/') ) { ?>
  <?php if( file_exists('../photos/'.$repertoire.'personnels/') ) { ?>
  <li><a href="trombinoscopes_admin.php?sousrub=vp#liste">Voir les personnels n'ayant pas de photos</a></li>
  <?php //} if( file_exists('../photos/eleves/') ) {?>
  <?php } if( file_exists('../photos/'.$repertoire.'eleves/') ) {?>
  <li><a href="trombinoscopes_admin.php?sousrub=ve#liste">Voir les élèves n'ayant pas de photos</a></li>
  <?php } ?>
 </ul>
</li>
</ul>


<?php if ( $sousrub === 've' ) {

	$cpt_eleve = '0';
	$requete_liste_eleve = "SELECT * FROM ".$prefix_base."eleves e, ".$prefix_base."j_eleves_classes jec, ".$prefix_base."classes c WHERE e.login = jec.login AND jec.id_classe = c.id GROUP BY e.login ORDER BY id_classe, nom, prenom ASC";
	$resultat_liste_eleve = mysql_query($requete_liste_eleve) or die('Erreur SQL !'.$requete_liste_eleve.'<br/>'.mysql_error());
        while ( $donnee_liste_eleve = mysql_fetch_array ($resultat_liste_eleve))
	{
		$photo = '';
		$eleve_login[$cpt_eleve] = $donnee_liste_eleve['login'];
		$eleve_nom[$cpt_eleve] = $donnee_liste_eleve['nom'];
		$eleve_prenom[$cpt_eleve] = $donnee_liste_eleve['prenom'];
		$eleve_classe[$cpt_eleve] = $donnee_liste_eleve['nom_complet'];
		$eleve_classe_court[$cpt_eleve] = $donnee_liste_eleve['classe'];
		$eleve_elenoet[$cpt_eleve] = $donnee_liste_eleve['elenoet'];
		$nom_photo = nom_photo($eleve_elenoet[$cpt_eleve]);
		//$photo = "../photos/eleves/".$nom_photo;
		$photo = $nom_photo;
		//if (($nom_photo != "") and (file_exists($photo))) { $eleve_photo[$cpt_eleve] = 'oui'; } else { $eleve_photo[$cpt_eleve] = 'non'; }
		if (($nom_photo) and (file_exists($photo))) { $eleve_photo[$cpt_eleve] = 'oui'; } else { $eleve_photo[$cpt_eleve] = 'non'; }
		$cpt_eleve = $cpt_eleve + 1;
	}

	?><a name="liste"></a><h2>Liste des élèves n'ayant pas de photos</h2>
	<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Elèves sans photo">
	   <tr>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Nom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Prénom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Classe</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Numéro élève</td>
	   </tr>
	<?php
	$cpt_eleve = '0'; $classe_passe = ''; $i = '1';
	while ( !empty($eleve_login[$cpt_eleve]) )
	{
	        if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
		if ( $eleve_photo[$cpt_eleve] === 'non' )
		{
			if ( $eleve_classe[$cpt_eleve] != $classe_passe and $cpt_eleve != '0' ) { ?><tr><td colspan="4">&nbsp;</td></tr><?php }
		    ?><tr style="<?php echo $couleur_cellule; ?>">
		        <td style="text-align: left;"><?php echo $eleve_nom[$cpt_eleve]; ?></td>
		        <td style="text-align: left;"><?php echo $eleve_prenom[$cpt_eleve]; ?></td>
		        <td style="text-align: center;"><?php echo $eleve_classe[$cpt_eleve].' ('.$eleve_classe_court[$cpt_eleve].')'; ?></td>
		        <td style="text-align: center;"><?php echo $eleve_elenoet[$cpt_eleve]; ?></td>
		      </tr><?php
		}
		$classe_passe = $eleve_classe[$cpt_eleve];
		$cpt_eleve = $cpt_eleve + 1;
	}
?>
</table><br/>
<?php }

if ( $sousrub === 'vp' ) {

	$cpt_personnel = '0';
	$requete_liste_personnel = "SELECT * FROM ".$prefix_base."utilisateurs u WHERE u.statut='professeur' ORDER BY nom, prenom ASC";
	$resultat_liste_personnel = mysql_query($requete_liste_personnel) or die('Erreur SQL !'.$requete_liste_personnel.'<br/>'.mysql_error());
        while ( $donnee_liste_personnel = mysql_fetch_array ($resultat_liste_personnel))
	{
		$photo = '';
		$personnel_login[$cpt_personnel] = $donnee_liste_personnel['login'];
		$personnel_nom[$cpt_personnel] = $donnee_liste_personnel['nom'];
		$personnel_prenom[$cpt_personnel] = $donnee_liste_personnel['prenom'];

		$codephoto = $personnel_login[$cpt_personnel];
		$nom_photo = nom_photo($codephoto,"personnels");
		//$photo = '../photos/personnels/'.$nom_photo;
		$photo = $nom_photo;
		//if (($nom_photo != "") and (file_exists($photo))) { $personnel_photo[$cpt_personnel] = 'oui'; } else { $personnel_photo[$cpt_personnel] = 'non'; }
		if (($nom_photo) and (file_exists($photo))) { $personnel_photo[$cpt_personnel] = 'oui'; } else { $personnel_photo[$cpt_personnel] = 'non'; }
		$cpt_personnel = $cpt_personnel + 1;
	}

	?><a name="liste"></a><h2>Liste des personnels n'ayant pas de photos</h2>
	<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Personnels sans photo">
	   <tr>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Nom</td>
	      <td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Prénom</td>
	   </tr>
	<?php
	$cpt_personnel = '0'; $i = '1';
	while ( !empty($personnel_login[$cpt_personnel]) )
	{
	        if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
		if ( $personnel_photo[$cpt_personnel] === 'non' )
		{
		    ?><tr style="<?php echo $couleur_cellule; ?>">
		        <td style="text-align: left;"><?php echo $personnel_nom[$cpt_personnel]; ?></td>
		        <td style="text-align: left;"><?php echo $personnel_prenom[$cpt_personnel]; ?></td>
		      </tr><?php
		}
		$cpt_personnel = $cpt_personnel + 1;
	}
?>
</table><br/>
<?php }

if ( $sousrub === 'de' ) {

	?><a name="validation"></a><div style="background-color: #FFFCDF; margin-left: 80px; margin-right: 80px; padding: 10px;  border-left: 5px solid #FF1F28; text-align: center; color: rgb(255, 0, 0); font-weight: bold;"><img src="../mod_absences/images/attention.png" alt="Attention" /><div style="margin: 10px;">Vous allez supprimer toutes les photos d'identité élève que contient le dossier photo de GEPI, êtes vous d'accord ?<br/><br/><a href="trombinoscopes_admin.php">NON</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="trombinoscopes_admin.php?sousrub=deok#supprime">OUI</a></div></div><?php
}

if ( $sousrub === 'dp' ) {

	?>
	<a name="validation"></a><div style="background-color: #FFFCDF; margin-left: 80px; margin-right: 80px; padding: 10px;  border-left: 5px solid #FF1F28; text-align: center; color: rgb(255, 0, 0); font-weight: bold;"><img src="../mod_absences/images/attention.png" alt="Attention" /><div style="margin: 10px;">Vous allez supprimer toutes les photos d'identité personnel que contient le dossier photo de GEPI, êtes vous d'accord ?<br/><br/><a href="trombinoscopes_admin.php">NON</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="trombinoscopes_admin.php?sousrub=dpok#supprime">OUI</a></div></div>
<?php
}


if ( $sousrub === 'deok' ) {

	// on liste les fichier du dossier photos/eleves
	$fichier_sup=array();
	//$folder = "../photos/eleves/";
	$folder = "../photos/".$repertoire."eleves/";
	$cpt_fichier = '0';
	$dossier = opendir($folder);
	while ($Fichier = readdir($dossier)) {
	  if ($Fichier != "." && $Fichier != ".." && $Fichier != "index.html") {
	    $nomFichier = $folder."".$Fichier;
	    $fichier_sup[$cpt_fichier] = $nomFichier;
	    $cpt_fichier = $cpt_fichier + 1;
	  }
	}
	closedir($dossier);

	//on supprime tout les fichiers
	$cpt_fichier = '0';
	?>
	<a name="supprime"></a>
	<!--h2>Liste des fichiers concernés et leurs états</h2-->
	<h2>Liste des fichiers concernés</h2>

	<?php
		if(count($fichier_sup)==0) {
			echo "<p style='margin-left: 50px;'>Le dossier <strong>$folder</strong> ne contient pas de photo.</p>\n";
		}
		else {
	?>

			<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Suppression">
			<tr>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Fichier</td>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Etat</td>
			</tr><?php $i = '1';
			while ( !empty($fichier_sup[$cpt_fichier]) )
			{
					if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
				if(file_exists($fichier_sup[$cpt_fichier]))
				{
					@unlink($fichier_sup[$cpt_fichier]);

					if(file_exists($fichier_sup[$cpt_fichier]))
					{ $etat = '<span style="color:red;">erreur, vous n\'avez pas les droits pour supprimer ce fichier</span>'; } else { $etat = 'supprimé'; }
					?>
				<tr style="<?php echo $couleur_cellule; ?>">
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $fichier_sup[$cpt_fichier]; ?></td>
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $etat; ?></td>
				</tr><?php
				}
			$cpt_fichier = $cpt_fichier + 1;
			}

			echo "</table>\n";
		}
}

if ( $sousrub === 'dpok' ) {

	// on liste les fichier du dossier photos/personnels
	$fichier_sup=array();
	//$folder = "../photos/personnels/";
	$folder = "../photos/".$repertoire."personnels/";
	$cpt_fichier = '0';
	$dossier = opendir($folder);
	while ($Fichier = readdir($dossier)) {
	  if ($Fichier != "." && $Fichier != ".." && $Fichier != "index.html") {
	    $nomFichier = $folder."".$Fichier;
	    $fichier_sup[$cpt_fichier] = $nomFichier;
	    $cpt_fichier = $cpt_fichier + 1;
	  }
	}
	closedir($dossier);

	//on supprime tout les fichiers
	$cpt_fichier = '0';
	?>
	<a name="supprime"></a>
	<!--h2>Liste des fichiers concernés et leurs états</h2-->
	<h2>Liste des fichiers concernés</h2>

	<?php
		if(count($fichier_sup)==0) {
			echo "<p style='margin-left: 50px;'>Le dossier <strong>$folder</strong> ne contient pas de photo.</p>\n";
		}
		else {
	?>

			<table cellpadding="1" cellspacing="1" style="margin: auto; border: 0px; background: #088CB9; color: #E0EDF1; text-align: center;" summary="Suppression">
			<tr>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Fichier</td>
				<td style="text-align: center; white-space: nowrap; padding-left: 2px; padding-right: 2px; font-weight: bold; color: #FFFFFF; padding-left: 2px; padding-right: 2px;">Etat</td>
			</tr><?php $i = '1';
			while ( !empty($fichier_sup[$cpt_fichier]) )
			{
					if ($i === '1') { $i = '2'; $couleur_cellule = 'background: #B7DDFF;'; } else { $couleur_cellule = 'background: #88C7FF;'; $i = '1'; }
				if(file_exists($fichier_sup[$cpt_fichier]))
				{
					@unlink($fichier_sup[$cpt_fichier]);

					if(file_exists($fichier_sup[$cpt_fichier]))
					{ $etat = '<span style="color:red;">erreur, vous n\'avez pas les droits pour supprimer ce fichier</span>'; } else { $etat = 'supprimé'; }
					?>
				<tr style="<?php echo $couleur_cellule; ?>">
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $fichier_sup[$cpt_fichier]; ?></td>
					<td style="text-align: left; padding-left: 2px; padding-right: 2px;"><?php echo $etat; ?></td>
				</tr><?php
				}
			$cpt_fichier = $cpt_fichier + 1;
			}

			echo "</table>\n";
		}
}


echo "<p><br/></p>\n";
require("../lib/footer.inc.php"); ?>
 *
 */


/****************************************************************
			BAS DE PAGE
****************************************************************/
$tbs_microtime	="";
$tbs_pmv="";
require_once ("../lib/footer_template.inc.php");

/****************************************************************
			On s'assure que le nom du gabarit est bien renseigné
****************************************************************/
if ((!isset($_SESSION['rep_gabarits'])) || (empty($_SESSION['rep_gabarits']))) {
	$_SESSION['rep_gabarits']="origine";
}

//==================================
// Décommenter la ligne ci-dessous pour afficher les variables $_GET, $_POST, $_SESSION et $_SERVER pour DEBUG:
// $affiche_debug=debug_var();


$nom_gabarit = '../templates/'.$_SESSION['rep_gabarits'].'/mod_trombinoscopes/trombinoscopes_admin_template.php';

$tbs_last_connection=""; // On n'affiche pas les dernières connexions
include($nom_gabarit);

// ------ on vide les tableaux -----
unset($menuAffiche);



?>
