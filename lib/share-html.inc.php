<?php
/**
 * Fonctions créant du html
 * 
 * 
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
 * 
 * @package Initialisation
 * @subpackage html
 *
*/

/**
 * 
 * @global int $GLOBALS['num_debut_colonnes_matieres']
 * @name $num_debut_colonnes_matieres
 */
$GLOBALS['num_debut_colonnes_matieres'] = 0;

/**
 * 
 * @global int $GLOBALS['num_debut_lignes_eleves']
 * @name $num_debut_lignes_eleves
 */
$GLOBALS['num_debut_lignes_eleves'] = 0;

/**
 * 
 * @global text $GLOBALS['vtn_coloriser_resultats']
 * @name $vtn_coloriser_resultats
 */
$GLOBALS['vtn_coloriser_resultats'] = '';

/**
 * 
 * @global array $GLOBALS['vtn_borne_couleur']
 * @name $vtn_borne_couleur
 */
$GLOBALS['vtn_borne_couleur'] = array();

/**
 * 
 * @global array $GLOBALS['vtn_couleur_texte']
 * @name $vtn_couleur_texte
 */
$GLOBALS['vtn_couleur_texte'] = array();

/**
 * 
 * @global array $GLOBALS['vtn_couleur_cellule']
 * @name $vtn_couleur_cellule
 */
$GLOBALS['vtn_couleur_cellule'] = array();

/**
 * 
 * @global text $GLOBALS['selected_eleve']
 * @name $selected_eleve
 */
$GLOBALS['selected_eleve'] = '';

/**
 * Construit du html pour les cahiers de textes
 *
 * @deprecated La requête SQL n'est plus valide
 * @param string $link Le lien
 * @param <type> $current_classe
 * @param <type> $current_matiere
 * @param integer $year année
 * @param integer $month le mois
 * @param integer $day le jour
 * @return string echo résultat
 */
function make_area_list_html($link, $current_classe, $current_matiere, $year, $month, $day) {
  echo "<strong><em>Cahier&nbsp;de&nbsp;texte&nbsp;de&nbsp;:</em></strong><br />";
  $appel_donnees = mysql_query("SELECT * FROM classes ORDER BY classe");
  $lignes = mysql_num_rows($appel_donnees);
  $i = 0;
  while($i < $lignes){
    $id_classe = mysql_result($appel_donnees, $i, "id");
    $appel_mat = mysql_query("SELECT DISTINCT m.* FROM matieres m, j_classes_matieres_professeurs j WHERE (j.id_classe='$id_classe' AND j.id_matiere=m.matiere) ORDER BY m.nom_complet");
    $nb_mat = mysql_num_rows($appel_mat);
    $j = 0;
    while($j < $nb_mat){
      $flag2 = "no";
      $matiere_nom = mysql_result($appel_mat, $j, "nom_complet");
      $matiere_nom_court = mysql_result($appel_mat, $j, "matiere");
      $id_matiere = mysql_result($appel_mat, $j, "matiere");
      $call_profs = mysql_query("SELECT * FROM j_classes_matieres_professeurs WHERE ( id_classe='$id_classe' and id_matiere = '$id_matiere') ORDER BY ordre_prof");
      $nombre_profs = mysql_num_rows($call_profs);
      $k = 0;
      while ($k < $nombre_profs) {
        $temp = my_strtoupper(@mysql_result($call_profs, $k, "id_professeur"));
        if ($temp == $_SESSION['login']) {$flag2 = "yes";}
        $k++;
      }
      if ($flag2 == "yes") {
        echo "<strong>";
        $display_class = mysql_result($appel_donnees, $i, "classe");
        if (($id_classe == $current_classe) and ($id_matiere == $current_matiere)) {
           echo ">$display_class&nbsp;-&nbsp;$matiere_nom&nbsp;($matiere_nom_court)&nbsp;";
        } else {
       echo "<a href=\"".$link."?id_classe=$id_classe&amp;id_matiere=$id_matiere&amp;year=$year&amp;month=$month&amp;day=$day\">$display_class&nbsp;-&nbsp;$matiere_nom&nbsp;($matiere_nom_court)</a>";

        }
        echo "</strong><br />";
      }
      $j++;
    }
    $i++;
  }
}



/**
 * Affichage de la liste des conteneurs
 *
 * @global array 
 * @global text 
 * @global int 
 * @global int 
 * @param int $id_conteneur Id du conteneur
 * @param int $periode_num Numéro de la période
 * @param text $empty existance de notes, no si le conteneur contient des notes (passage par référence) 
 * @param int $ver_periode Etat du vérouillage de la période
 * @return text no si le conteneur contient des notes, yes sinon
 * @see getSettingValue()
 * @see get_nom_prenom_eleve()
 * @see creer_div_infobulle()
 * @see add_token_in_url()
 * @see traitement_magic_quotes()
 */
function affiche_devoirs_conteneurs($id_conteneur,$periode_num, &$empty, $ver_periode) {
	global $tabdiv_infobulle, $gepiClosedPeriodLabel, $id_groupe, $eff_groupe;
    
	if((isset($id_groupe))&&(!isset($eff_groupe))) {
		$sql="SELECT 1=1 FROM j_eleves_groupes WHERE id_groupe='$id_groupe' AND periode='$periode_num';";
		//echo "$sql<br />";
		$res_ele_grp=mysql_query($sql);
		$eff_groupe=mysql_num_rows($res_ele_grp);
	}
	//
	// Cas particulier de la racine
	$gepi_denom_boite=getSettingValue("gepi_denom_boite");
	if(getSettingValue("gepi_denom_boite_genre")=='m'){
		$message_cont = "Etes-vous sûr de vouloir supprimer le ".getSettingValue("gepi_denom_boite")." ci-dessous ?";
		$message_cont_non_vide = "Le ".getSettingValue("gepi_denom_boite")." est non vide. Il ne peut pas être supprimé.";
	}
	else{
		$message_cont = "Etes-vous sûr de vouloir supprimer la ".getSettingValue("gepi_denom_boite")." ci-dessous ?";
		$message_cont_non_vide = "La ".getSettingValue("gepi_denom_boite")." est non vide. Elle ne peut pas être supprimée.";
	}
	$message_dev = "Etes-vous sûr de vouloir supprimer l\\'évaluation ci-dessous et les notes qu\\'elle contient ?";
	$sql="SELECT * FROM cn_conteneurs WHERE (parent='0' and id_racine='$id_conteneur')";
	$appel_conteneurs = mysql_query($sql);
	$nb_cont = mysql_num_rows($appel_conteneurs);
	if ($nb_cont != 0) {
		echo "<ul>\n";
		$id_cont = mysql_result($appel_conteneurs, 0, 'id');
		$id_parent = mysql_result($appel_conteneurs, 0, 'parent');
		$id_racine = mysql_result($appel_conteneurs, 0, 'id_racine');
		$nom_conteneur = mysql_result($appel_conteneurs, 0, 'nom_court');
		echo "<li>\n";
		echo htmlspecialchars($nom_conteneur);
		if ($ver_periode <= 1) {
			echo " (<strong>".$gepiClosedPeriodLabel."</strong>) ";
		}
		echo "- <a href='saisie_notes.php?id_conteneur=$id_cont'>Visualisation</a> - <a href = 'add_modif_conteneur.php?id_conteneur=$id_cont&amp;mode_navig=retour_index'>Configuration</a>\n";

		$ponderation_cont=mysql_result($appel_conteneurs, 0, 'ponderation');
		if($ponderation_cont!='0.0') {
			$message_ponderation="La meilleure note de la ".getSettingValue("gepi_denom_boite")." est pondérée d'un coefficient +$ponderation_cont";
			echo " - <img src='../images/icons/flag.png' width='17' height='18' alt=\"$message_ponderation\" title=\"$message_ponderation\" />";
		}

		$appel_dev = mysql_query("select * from cn_devoirs where id_conteneur='$id_cont' order by date");
		$nb_dev  = mysql_num_rows($appel_dev);
		if ($nb_dev != 0) {$empty = 'no';}
		if ($ver_periode >= 2) {
			$j = 0;
			if($nb_dev>0){
				echo "<ul>\n";
				while ($j < $nb_dev) {
					if (getSettingValue("utiliser_sacoche") == 'yes') {
						echo '<form id="sacoche_form_'.$j.'" method="POST" action="'.getSettingValue("sacocheUrl").'/index.php?sso&page=professeur_eval&section=groupe">';
						echo '<input type="hidden" name="id" value="'.getSettingValue("sacoche_base").'"/>';
						echo '<input type="hidden" name="page" value="professeur_eval"/>';
						echo '<input type="hidden" name="section" value="groupe"/>';
						echo '<input type="hidden" name="source" value="distant-gepi-saml"/>';//source simplesaml pour préselectionner la source dans le module multiauth et éviter de choisir le webmestre
						//encodage du devoir
						mysql_data_seek($appel_dev, $j);
						$devoir_array = mysql_fetch_array($appel_dev);
						$devoir_array = array_map_deep('ensure_utf8', $devoir_array);
						echo '<input type="hidden" name="period_num" value=\''.$periode_num.'\'/>';
						echo '<input type="hidden" name="gepi_cn_devoirs_array" value="'.htmlspecialchars(json_encode($devoir_array),ENT_COMPAT,'UTF-8').'"/>';
						$group_array = get_group($id_groupe);
						//on va purger un peut notre array
						unset($group_array['classes']);
						unset($group_array['matieres']);
						unset($group_array['eleves']['all']);
						for ($i=0;$i<5;$i++) {
							if ($i != $periode_num) {
								unset($group_array['eleves'][''.$i]);
							}
						}
						$current_group = array_map_deep('ensure_utf8', $group_array);
						echo '<input type="hidden" name="gepi_current_group" value="'.htmlspecialchars(json_encode($current_group),ENT_COMPAT,'UTF-8').'"/>';
						echo '</form>';
					}
					
					$nom_dev = mysql_result($appel_dev, $j, 'nom_court');
					$id_dev = mysql_result($appel_dev, $j, 'id');
					echo "<li>\n";
					//echo "<font color='green'>$nom_dev</font>";
					echo "<span style='color:green;'>$nom_dev</span>";
					echo " - <a href='saisie_notes.php?id_conteneur=$id_cont&amp;id_devoir=$id_dev'>Saisie</a>";

					$sql="SELECT 1=1 FROM cn_notes_devoirs cnd, j_eleves_classes jec WHERE cnd.id_devoir='$id_dev' AND cnd.statut!='v' AND jec.login=cnd.login AND jec.periode='$periode_num';";
					$res_eff_dev=mysql_query($sql);
					$eff_dev=mysql_num_rows($res_eff_dev);
					echo " <span title=\"Effectif des notes saisies/effectif total de l'enseignement\" style='font-size:small;";
					if(isset($eff_groupe)) {if($eff_dev==$eff_groupe) {echo "color:green;";} else {echo "color:red;";}}
					echo "'>($eff_dev";
					if(isset($eff_groupe)) {echo "/$eff_groupe";}
					echo ")</span>";

					// Pour détecter une anomalie:
					 $sql="SELECT * FROM cn_notes_devoirs cnd, j_eleves_classes jec WHERE cnd.id_devoir='$id_dev' AND cnd.statut!='v' AND jec.login=cnd.login AND jec.periode='$periode_num' AND jec.login not in (select login from j_eleves_groupes where id_groupe='$id_groupe' and periode='$periode_num');";
					$test_anomalie=mysql_query($sql);
					if(mysql_num_rows($test_anomalie)>0) {
						$titre_infobulle="Note pour un fantôme";
						$texte_infobulle="Une ou des notes existent pour un ou des élèves qui ne sont plus inscrits dans cet enseignement&nbsp;:<br />";
						$cpt_ele_anomalie=0;
						while($lig_anomalie=mysql_fetch_object($test_anomalie)) {
							if($cpt_ele_anomalie>0) {$texte_infobulle.=", ";}
							$texte_infobulle.=get_nom_prenom_eleve($lig_anomalie->login,'avec_classe')."&nbsp;(<i>";
							if($lig_anomalie->statut=='') {$texte_infobulle.=$lig_anomalie->note;}
							elseif($lig_anomalie->statut=='v') {$texte_infobulle.="_";}
							else {$texte_infobulle.=$lig_anomalie->statut;}
							$texte_infobulle.="</i>)";
							$cpt_ele_anomalie++;
						}
						$texte_infobulle.="<br />";
						$texte_infobulle.="Cliquer <a href='".$_SERVER['PHP_SELF']."?id_groupe=$id_groupe&amp;periode_num=$periode_num&amp;clean_anomalie_dev=$id_dev".add_token_in_url()."'>ici</a> pour supprimer les notes associées?";
						$tabdiv_infobulle[]=creer_div_infobulle('anomalie_'.$id_dev,$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');

						echo " <a href=\"#\" onclick=\"afficher_div('anomalie_$id_dev','y',100,100);return false;\"><img src='../images/icons/flag.png' width='17' height='18' /></a>";
					}

					if (getSettingValue("utiliser_sacoche") == 'yes') {
						echo " - <a href='#' onclick=\"document.getElementById('sacoche_form_".$j."').submit();\">Évaluer par compétence</a>";
					}

					echo " - <a href = 'add_modif_dev.php?id_conteneur=$id_conteneur&amp;id_devoir=$id_dev&amp;mode_navig=retour_index'>Configuration</a>";

					$note_sur=mysql_result($appel_dev, $j, 'note_sur');
					if($note_sur!='20') {
						echo " (<em><span title='Note sur $note_sur'>/$note_sur</span></em>)";
					}

					$display_parents=mysql_result($appel_dev, $j, 'display_parents');
					$coef=mysql_result($appel_dev, $j, 'coef');
					echo " (<i><span title='Coefficient $coef'>$coef</span> ";
					if($display_parents==1) {echo "<img src='../images/icons/visible.png' width='19' height='16' title='Evaluation visible sur le relevé de notes' alt='Evaluation visible sur le relevé de notes' />";}
					else {echo " <img src='../images/icons/invisible.png' width='19' height='16' title='Evaluation non visible sur le relevé de notes' alt='Evaluation non visible sur le relevé de notes' />\n";}
					echo "</i>)";
					echo " - <a href = 'index.php?id_racine=$id_racine&amp;del_dev=$id_dev".add_token_in_url()."' onclick=\"return confirmlink(this, 'suppression de ".traitement_magic_quotes($nom_dev)."', '".$message_dev."')\">Suppression</a>\n";
					echo "</li>\n";
					$j++;
				}
				echo "</ul>\n";
			}
		}
		echo "</li>\n";
		echo "</ul>\n";
	}
	if ($ver_periode >= 2) {
		$appel_conteneurs = mysql_query("SELECT * FROM cn_conteneurs WHERE (parent='$id_conteneur') order by nom_court");
		$nb_cont = mysql_num_rows($appel_conteneurs);
		if($nb_cont>0) {
			echo "<ul>\n";
			$i = 0;
			while ($i < $nb_cont) {
				$id_cont = mysql_result($appel_conteneurs, $i, 'id');
				$id_parent = mysql_result($appel_conteneurs, $i, 'parent');
				$id_racine = mysql_result($appel_conteneurs, $i, 'id_racine');
				$nom_conteneur = mysql_result($appel_conteneurs, $i, 'nom_court');
				if ($id_cont != $id_parent) {
					echo "<li>\n";
					echo "$nom_conteneur - <a href='saisie_notes.php?id_conteneur=$id_cont'>Visualisation</a>";
					echo " - <a href = 'add_modif_conteneur.php?id_conteneur=$id_cont&amp;mode_navig=retour_index'>Configuration</a>\n";

					$display_bulletin=mysql_result($appel_conteneurs, $i, 'display_bulletin');
					$coef=mysql_result($appel_conteneurs, $i, 'coef');
					echo " (<i><span title='Coefficient $coef'>$coef</span> ";
					if($display_bulletin==1) {echo "<img src='../images/icons/visible.png' width='19' height='16' title='$gepi_denom_boite visible sur le bulletin' alt='$gepi_denom_boite visible sur le bulletin' />";}
					else {echo " <img src='../images/icons/invisible.png' width='19' height='16' title='$gepi_denom_boite non visible sur le bulletin' alt='$gepi_denom_boite non visible sur le bulletin' />\n";}
					echo "</i>)";

					$ponderation_cont=mysql_result($appel_conteneurs, $i, 'ponderation');
					if($ponderation_cont!='0.0') {
						$message_ponderation="La meilleure note de la ".getSettingValue("gepi_denom_boite")." est pondérée d'un coefficient +$ponderation_cont";
						echo " - <img src='../images/icons/flag.png' width='17' height='18' alt=\"$message_ponderation\" title=\"$message_ponderation\" />";
					}

					$appel_dev = mysql_query("select * from cn_devoirs where id_conteneur='$id_cont' order by date");
					$nb_dev  = mysql_num_rows($appel_dev);
					if ($nb_dev != 0) {$empty = 'no';}

					// Existe-t-il des sous-conteneurs?
					$sql="SELECT 1=1 FROM cn_conteneurs WHERE (parent='$id_cont')";
					$test_sous_cont=mysql_query($sql);
					$nb_sous_cont=mysql_num_rows($test_sous_cont);

					if(($nb_dev==0)&&($nb_sous_cont==0)) {
						echo " - <a href = 'index.php?id_racine=$id_racine&amp;del_cont=$id_cont".add_token_in_url()."' onclick=\"return confirmlink(this, 'suppression de ".traitement_magic_quotes($nom_conteneur)."', '".$message_cont."')\">Suppression</a>\n";
					}
					else {
						echo " - <a href = '#' onclick='alert(\"$message_cont_non_vide\")'><font color='gray'>Suppression</font></a>\n";
					}

					$j = 0;
					if($nb_dev>0) {
						echo "<ul>\n";
						while ($j < $nb_dev) {
							$nom_dev = mysql_result($appel_dev, $j, 'nom_court');
							$id_dev = mysql_result($appel_dev, $j, 'id');
							echo "<li>\n";
							echo "<font color='green'>$nom_dev</font> - <a href='saisie_notes.php?id_conteneur=$id_cont&amp;id_devoir=$id_dev'>Saisie</a>";

							$sql="SELECT 1=1 FROM cn_notes_devoirs cnd, j_eleves_classes jec WHERE cnd.id_devoir='$id_dev' AND cnd.statut!='-' AND cnd.statut!='v' AND jec.login=cnd.login AND jec.periode='$periode_num';";
							$res_eff_dev=mysql_query($sql);
							$eff_dev=mysql_num_rows($res_eff_dev);
							echo " <span title=\"Effectif des notes saisies/effectif total de l'enseignement\" style='font-size:small;";
							if(isset($eff_groupe)) {if($eff_dev==$eff_groupe) {echo "color:green;";} else {echo "color:red;";}}
							echo "'>($eff_dev";
							if(isset($eff_groupe)) {echo "/$eff_groupe";}
							echo ")</span>";

							// Pour détecter une anomalie:
							$sql="SELECT * FROM cn_notes_devoirs cnd, j_eleves_classes jec WHERE cnd.id_devoir='$id_dev' AND cnd.statut!='v' AND jec.login=cnd.login AND jec.periode='$periode_num' AND jec.login not in (select login from j_eleves_groupes where id_groupe='$id_groupe' and periode='$periode_num');";
							$test_anomalie=mysql_query($sql);
							if(mysql_num_rows($test_anomalie)>0) {
								$titre_infobulle="Note pour un fantôme";
								$texte_infobulle="Une ou des notes existent pour un ou des élèves qui ne sont plus inscrits dans cet enseignement&nbsp;:<br />";
								$cpt_ele_anomalie=0;
								while($lig_anomalie=mysql_fetch_object($test_anomalie)) {
									if($cpt_ele_anomalie>0) {$texte_infobulle.=", ";}
									$texte_infobulle.=get_nom_prenom_eleve($lig_anomalie->login,'avec_classe')."&nbsp;(<i>";
									if($lig_anomalie->statut=='') {$texte_infobulle.=$lig_anomalie->note;}
									elseif($lig_anomalie->statut=='v') {$texte_infobulle.="_";}
									else {$texte_infobulle.=$lig_anomalie->statut;}
									$texte_infobulle.="</i>)";
									$cpt_ele_anomalie++;
								}
								$texte_infobulle.="<br />";
								$texte_infobulle.="Cliquer <a href='".$_SERVER['PHP_SELF']."?id_groupe=$id_groupe&amp;periode_num=$periode_num&amp;clean_anomalie_dev=$id_dev".add_token_in_url()."'>ici</a> pour supprimer les notes associées?";
								$tabdiv_infobulle[]=creer_div_infobulle('anomalie_'.$id_dev,$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');
		
								echo " <a href=\"#\" onclick=\"afficher_div('anomalie_$id_dev','y',100,100);return FALSE;\"><img src='../images/icons/flag.png' width='17' height='18' /></a>";
							}

							echo " - <a href = 'add_modif_dev.php?id_conteneur=$id_conteneur&amp;id_devoir=$id_dev&amp;mode_navig=retour_index'>Configuration</a>";

							$note_sur=mysql_result($appel_dev, $j, 'note_sur');
							if($note_sur!='20') {
								echo " (<em><span title='Note sur $note_sur'>/$note_sur</span></em>)";
							}

							$display_parents=mysql_result($appel_dev, $j, 'display_parents');
							$coef=mysql_result($appel_dev, $j, 'coef');
							echo " (<i><span title='Coefficient $coef'>$coef</span> ";
							if($display_parents==1) {echo "<img src='../images/icons/visible.png' width='19' height='16' title='Evaluation visible sur le relevé de notes' alt='Evaluation visible sur le relevé de notes' />";}
							else {echo " <img src='../images/icons/invisible.png' width='19' height='16' title='Evaluation non visible sur le relevé de notes' alt='Evaluation non visible sur le relevé de notes' />\n";}
							echo "</i>)";

							echo " - <a href = 'index.php?id_racine=$id_racine&amp;del_dev=$id_dev".add_token_in_url()."' onclick=\"return confirmlink(this, 'suppression de ".traitement_magic_quotes($nom_dev)."', '".$message_dev."')\">Suppression</a>\n";
							echo "</li>\n";
							$j++;
						}
						echo "</ul>\n";
					}
				}
				if ($id_conteneur != $id_cont) {affiche_devoirs_conteneurs($id_cont,$periode_num, $empty,$ver_periode);}
				if ($id_cont != $id_parent) {
					echo "</li>\n";
				}
				$i++;
			}
			echo "</ul>\n";
		}
	}
	if ($empty != 'no') return 'yes';
}


/**
 * Affiche l'AID sur le bulletin
 *
 * @global type
 * @param type $affiche_graph
 * @param type $affiche_rang
 * @param type $affiche_coef
 * @param type $test_coef
 * @param type $affiche_nbdev
 * @param type $indice_aid
 * @param type $aid_id
 * @param type $current_eleve_login
 * @param type $periode_num
 * @param type $id_classe
 * @param type $style_bulletin 
 */
function affich_aid($affiche_graph, $affiche_rang, $affiche_coef, $test_coef,$affiche_nbdev,$indice_aid, $aid_id,$current_eleve_login,$periode_num,$id_classe,$style_bulletin) {
    //============================
    // AJOUT: boireaus
    global $min_max_moyclas;
    //============================

    $call_data = mysql_query("SELECT * FROM aid_config WHERE indice_aid = $indice_aid");
    $AID_NOM_COMPLET = @mysql_result($call_data, 0, "nom_complet");
    $note_max = @mysql_result($call_data, 0, "note_max");
    $type_note = @mysql_result($call_data, 0, "type_note");
    $message = @mysql_result($call_data, 0, "message");
    $display_nom = @mysql_result($call_data, 0, "display_nom");
    $display_end = @mysql_result($call_data, 0, "display_end");


    $aid_nom_query = mysql_query("SELECT nom FROM aid WHERE (id='$aid_id' and indice_aid='$indice_aid')");
    $aid_nom = @mysql_result($aid_nom_query, 0, "nom");
    //------
    // On regarde maintenant quelle sont les profs responsables de cette AID
    $aid_prof_resp_query = mysql_query("SELECT id_utilisateur FROM j_aid_utilisateurs WHERE (id_aid='$aid_id'  and indice_aid='$indice_aid')");
    $nb_lig = mysql_num_rows($aid_prof_resp_query);
    $n = '0';
    while ($n < $nb_lig) {
        $aid_prof_resp_login[$n] = mysql_result($aid_prof_resp_query, $n, "id_utilisateur");
        $n++;
    }
    //------
    // On appelle l'appréciation de l'élève, et sa note
    //------
    $current_eleve_aid_appreciation_query = mysql_query("SELECT * FROM aid_appreciations WHERE (login='$current_eleve_login' AND periode='$periode_num' and id_aid='$aid_id' and indice_aid='$indice_aid')");
    $current_eleve_aid_appreciation = @mysql_result($current_eleve_aid_appreciation_query, 0, "appreciation");
    $periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe'");
    $periode_max = mysql_num_rows($periode_query);
    if ($type_note == 'last') {$last_periode_aid = min($periode_max,$display_end);}
    if (($type_note == 'every') or (($type_note == 'last') and ($periode_num == $last_periode_aid))) {
        $place_eleve = "";
        $current_eleve_aid_note = @mysql_result($current_eleve_aid_appreciation_query, 0, "note");
        $current_eleve_aid_statut = @mysql_result($current_eleve_aid_appreciation_query, 0, "statut");
        if (($current_eleve_aid_statut == '') and ($note_max != 20) ) {
            $current_eleve_aid_appreciation = "(note sur ".$note_max.") ".$current_eleve_aid_appreciation;
        }
        if ($current_eleve_aid_note == '') {
            $current_eleve_aid_note = '-';
        } else {
            if ($affiche_graph == 'y')  {
                if ($current_eleve_aid_note<5) { $place_eleve=6;}
                if (($current_eleve_aid_note>=5) and ($current_eleve_aid_note<8))  { $place_eleve=5;}
                if (($current_eleve_aid_note>=8) and ($current_eleve_aid_note<10)) { $place_eleve=4;}
                if (($current_eleve_aid_note>=10) and ($current_eleve_aid_note<12)) {$place_eleve=3;}
                if (($current_eleve_aid_note>=12) and ($current_eleve_aid_note<15)) { $place_eleve=2;}
                if ($current_eleve_aid_note>=15) { $place_eleve=1;}
            }
            $current_eleve_aid_note=number_format($current_eleve_aid_note,1, ',', ' ');
        }
        $aid_note_min_query = mysql_query("SELECT MIN(note) note_min FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");

        $aid_note_min = @mysql_result($aid_note_min_query, 0, "note_min");
        if ($aid_note_min == '') {
            $aid_note_min = '-';
        } else {
            $aid_note_min=number_format($aid_note_min,1, ',', ' ');
        }
        $aid_note_max_query = mysql_query("SELECT MAX(note) note_max FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
        $aid_note_max = @mysql_result($aid_note_max_query, 0, "note_max");

        if ($aid_note_max == '') {
            $aid_note_max = '-';
        } else {
            $aid_note_max=number_format($aid_note_max,1, ',', ' ');
        }

        $aid_note_moyenne_query = mysql_query("SELECT round(avg(note),1) moyenne FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid')");
        $aid_note_moyenne = @mysql_result($aid_note_moyenne_query, 0, "moyenne");
        if ($aid_note_moyenne == '') {
            $aid_note_moyenne = '-';
        } else {
            $aid_note_moyenne=number_format($aid_note_moyenne,1, ',', ' ');
        }

    }
    //------
    // On affiche l'appréciation aid :
    //------
    echo "<tr>\n<td style=\"height: ".getSettingValue("col_hauteur")."px; width: ".getSettingValue("col_matiere_largeur")."px;\"><span class='$style_bulletin'><strong>$AID_NOM_COMPLET</strong><br />";
    $chaine_prof="";
    $n = '0';
    while ($n < $nb_lig) {
        $chaine_prof.=affiche_utilisateur($aid_prof_resp_login[$n],$id_classe)."<br />";
        $n++;
    }
    if($n!=0){
	echo "<em>".$chaine_prof."</em>";
    }
    echo "</span></td>\n";
    if ($test_coef != 0 AND $affiche_coef == "y") echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='".$style_bulletin."'>-</span></td>\n";

    if ($affiche_nbdev=="y"){echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='".$style_bulletin."'>-</span></td>\n";}

    if (($type_note == 'every') or (($type_note == 'last') and ($periode_num == $last_periode_aid))) {
	//==========================
	// MODIF: boireaus
	if($min_max_moyclas!=1){
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\">";
		echo "<span class='$style_bulletin'>$aid_note_min</span></td>\n";

		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\">";
		echo "<span class='$style_bulletin'>$aid_note_max</span></td>\n";

		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\">";
		echo "<span class='$style_bulletin'>$aid_note_moyenne</span></td>\n";
	}
	else{
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\">";
		echo "<span class='$style_bulletin'>$aid_note_min<br />\n";
		echo "$aid_note_max<br />\n";
		echo "$aid_note_moyenne</span></td>\n";
	}
	//==========================

	echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\">";
	echo "<span class='$style_bulletin'><strong>";
	if ($current_eleve_aid_statut == '') {
		echo $current_eleve_aid_note;
	} else if ($current_eleve_aid_statut == 'other') {
		echo "-";
	} else {
		echo $current_eleve_aid_statut;
	}
	echo "</strong></span></td>\n";
    } else {
	//==========================
	// MODIF: boireaus
	if($min_max_moyclas!=1){
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='$style_bulletin'>-</span></td>\n";
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='$style_bulletin'>-</span></td>\n";
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='$style_bulletin'>-</span></td>\n";
	}
	else{
		// On ne met pas trois tirets.
		echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='$style_bulletin'>-</span></td>\n";
	}
	//==========================
	echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='$style_bulletin'>-</span></td>\n";
    }
    if ($affiche_graph == 'y')  {
      if (($type_note == 'every') or (($type_note == 'last') and ($periode_num == $last_periode_aid)))  {
        $quartile1_classe = sql_query1("SELECT COUNT( a.note ) as quartile1 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=15)");
        $quartile2_classe = sql_query1("SELECT COUNT( a.note ) as quartile2 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=12 AND a.note<15)");
        $quartile3_classe = sql_query1("SELECT COUNT( a.note ) as quartile3 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=10 AND a.note<12)");
        $quartile4_classe = sql_query1("SELECT COUNT( a.note ) as quartile4 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=8 AND a.note<10)");
        $quartile5_classe = sql_query1("SELECT COUNT( a.note ) as quartile5 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note>=5 AND a.note<8)");
        $quartile6_classe = sql_query1("SELECT COUNT( a.note ) as quartile6 FROM aid_appreciations a, j_eleves_classes j WHERE (a.login = j.login and j.id_classe = '$id_classe' and a.statut='' and a.periode = '$periode_num' and j.periode='$periode_num' and a.indice_aid='$indice_aid' AND a.note<5)");
        echo "<td style=\"text-align: center; \"><img height=40 witdh=40 src='../visualisation/draw_artichow4.php?place_eleve=$place_eleve&temp1=$quartile1_classe&temp2=$quartile2_classe&temp3=$quartile3_classe&temp4=$quartile4_classe&temp5=$quartile5_classe&temp6=$quartile6_classe&nb_data=7' /></td>\n";
     } else
      echo "<td style=\"text-align: center; \"><span class='".$style_bulletin."'>-</span></td>\n";
    }
    if ($affiche_rang == 'y') echo "<td style=\"text-align: center; width: ".getSettingValue("col_note_largeur")."px;\"><span class='".$style_bulletin."'>-</span></td>\n";
    if (getSettingValue("bull_affiche_appreciations") == 'y') {
        echo "<td style=\"\" colspan=\"2\"><span class='$style_bulletin'>";
        if (($message != '') or ($display_nom == 'y')) {
            echo "$message ";
            if ($display_nom == 'y') {echo "<strong>$aid_nom</strong><br />";}
        }
    }
    echo "$current_eleve_aid_appreciation</span></td>\n</tr>\n";
    //------
}

/**
 * Affiche un tableau pour règler la taille d'un tableau
 * 
 * Utilisé uniquement dans prepa_conseil/index1.php
 *
 * @param int $larg_tab largeur en pixel
 * @param int $bord bords en pixel
 */
function parametres_tableau($larg_tab, $bord) {
    echo "<table border='1' width='680' cellspacing='1' cellpadding='1' summary=\"Tableau de paramètres\">\n";
    echo "<tr><td><span class=\"norme\">largeur en pixel : <input type=\"text\" name=\"larg_tab\" size=\"3\" value=\"".$larg_tab."\" />\n";
    echo "bords en pixel : <input type=\"text\" name=\"bord\" size=\"3\" value=\"".$bord."\" />\n";
    echo "<input type=\"submit\" value=\"Valider\" />\n";
    echo "</span></td></tr></table>\n";
}

/**
 * Affiche un tableau
 *
 * Utilisé uniquement dans prepa_conseil et visu_toutes_notes2.php
 * 
 * @global type 
 * @global type 
 * @global type 
 * @global type
 * @global type 
 * @global type 
 * @param type $nombre_lignes
 * @param type $nb_col
 * @param type $ligne1
 * @param type $col
 * @param type $larg_tab
 * @param type $bord
 * @param type $col1_centre
 * @param type $col_centre
 * @param type $couleur_alterne 
 */
function affiche_tableau($nombre_lignes, $nb_col, $ligne1, $col, $larg_tab, $bord, $col1_centre, $col_centre, $couleur_alterne) {
    global $num_debut_colonnes_matieres, $num_debut_lignes_eleves, $vtn_coloriser_resultats, $vtn_borne_couleur, $vtn_couleur_texte, $vtn_couleur_cellule;

	echo "<table border=\"$bord\" class='boireaus' cellspacing=\"0\" width=\"$larg_tab\" cellpadding=\"1\" summary=\"Tableau\">\n";
    echo "<tr>\n";
    $j = 1;
    while($j < $nb_col+1) {
        echo "<th class='small' id='td_ligne1_$j'>$ligne1[$j]</th>\n";
        $j++;
    }
    echo "</tr>\n";
    $i = "0";
    $bg_color = "";
    $flag = "1";
    $alt=1;
    while($i < $nombre_lignes) {
        if((isset($couleur_alterne))&&($couleur_alterne=='y')) {
            if ($flag==1) {$bg_color = "bgcolor=\"#C0C0C0\"";} else {$bg_color = "     ";}
        }

	    $alt=$alt*(-1);
        echo "<tr class='";
		if((isset($couleur_alterne))&&($couleur_alterne=='y')) {echo "lig$alt ";}
		echo "white_hover'>\n";
        $j = 1;
        while($j < $nb_col+1) {
            if ((($j == 1) and ($col1_centre == 0)) or (($j != 1) and ($col_centre == 0))) {

				echo "<td class='small' ";
				if(!preg_match("/Rang de l/",$ligne1[$j])) {
					if(($vtn_coloriser_resultats=='y')&&($j>=$num_debut_colonnes_matieres)&&($i>=$num_debut_lignes_eleves)) {
						if(mb_strlen(preg_replace('/[0-9.,]/','',$col[$j][$i]))==0) {
							for($loop=0;$loop<count($vtn_borne_couleur);$loop++) {
								if(preg_replace('/,/','.',$col[$j][$i])<=preg_replace('/,/','.',$vtn_borne_couleur[$loop])) {
									echo " style='";
									if($vtn_couleur_texte[$loop]!='') {echo "color:$vtn_couleur_texte[$loop]; ";}
									if($vtn_couleur_cellule[$loop]!='') {echo "background-color:$vtn_couleur_cellule[$loop]; ";}
									echo "'";
									break;
								}
							}
						}
					}
				}
				echo ">{$col[$j][$i]}</td>\n";

            } else {
				echo "<td align=\"center\" class='small' ";
				if(!preg_match("/Rang de l/",$ligne1[$j])) {
					if(($vtn_coloriser_resultats=='y')&&($j>=$num_debut_colonnes_matieres)&&($i>=$num_debut_lignes_eleves)) {
						if(mb_strlen(preg_replace('/[0-9.,]/','',$col[$j][$i]))==0) {
							for($loop=0;$loop<count($vtn_borne_couleur);$loop++) {
								if(preg_replace('/,/','.',$col[$j][$i])<=preg_replace('/,/','.',$vtn_borne_couleur[$loop])) {
									echo " style='";
									if($vtn_couleur_texte[$loop]!='') {echo "color:$vtn_couleur_texte[$loop]; ";}
									if($vtn_couleur_cellule[$loop]!='') {echo "background-color:$vtn_couleur_cellule[$loop]; ";}
									echo "'";
									break;
								}
							}
						}
					}
				}
				echo ">{$col[$j][$i]}</td>\n";
            }
            $j++;
        }
        echo "</tr>\n";
        if ($flag == "1") {$flag = "0";} else {$flag = "1";}
        $i++;
    }
    echo "</table>\n";
}



/**
 * Crée un formulaire avec une zone de sélection contenant les classes
 *
 * @param type $link lien vers la page à atteindre ensuite
 * @param type $current Id de la classe courante pour qu'elle soit par défaut
 * @param type $year l'année
 * @param type $month le mois
 * @param type $day le jour
 * @return text la balise <form> complète
 */
function make_classes_select_html($link, $current, $year, $month, $day)

{
  // Pour le multisite, on doit récupérer le RNE de l'établissement
  $rne = isset($_GET['rne']) ? $_GET['rne'] : (isset($_POST['rne']) ? $_POST['rne'] : 'aucun');
  $aff_input_rne = $aff_get_rne = NULL;
  if ($rne != 'aucun') {
	$aff_input_rne = '<input type="hidden" name="rne" value="' . $rne . '" />' . "\n";
	$aff_get_rne = '&amp;rne=' . $rne;
  }
  $out_html = "<form name=\"classe\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\"><strong><em>Classe :</em></strong><br />
  " . $aff_input_rne . "
  <select name=\"classe\" onchange=\"classe_go()\">";

  $out_html .= "<option value=\"".$link."?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;id_classe=-1\">(Choisissez une classe)";


  if (isset($_SESSION['statut']) && ($_SESSION['statut']=='scolarite'
		  && getSettingValue('GepiAccesCdtScolRestreint')=="yes")){
  $sql = "SELECT DISTINCT c.id, c.classe
	FROM classes c, j_groupes_classes jgc, ct_entry ct, j_scol_classes jsc
	WHERE (c.id = jgc.id_classe
	  AND jgc.id_groupe = ct.id_groupe
	  AND jsc.id_classe=jgc.id_classe
	  AND jsc.login='".$_SESSION ['login']."'
		)
	ORDER BY classe ;";

  } else if (isset($_SESSION['statut']) && ($_SESSION['statut']=='cpe'
		  && getSettingValue('GepiAccesCdtCpeRestreint')=="yes")){


	$sql = "SELECT DISTINCT c.id, c.classe
	  FROM classes c, j_groupes_classes jgc, ct_entry ct, j_eleves_cpe jec,j_eleves_classes jecl
	  WHERE (c.id = jgc.id_classe
	  AND jgc.id_groupe = ct.id_groupe
	  AND jec.cpe_login = '".$_SESSION ['login']."'
	  AND jec.e_login = jecl.login
	  AND jecl.id_classe = jgc.id_classe)
	  ORDER BY classe ;";
  }else{


	$sql = "SELECT DISTINCT c.id, c.classe
	  FROM classes c, j_groupes_classes jgc, ct_entry ct
	  WHERE (c.id = jgc.id_classe
	  AND jgc.id_groupe = ct.id_groupe)
	  ORDER BY classe";
  }

  //GepiAccesCdtCpeRestreint

  $res = sql_query($sql);


  if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
  {
    $selected = ($row[0] == $current) ? "selected" : "";
    $link2 = "$link?year=$year&amp;month=$month&amp;day=$day&amp;id_classe=$row[0]" . $aff_get_rne;
    $out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row[1]);
  }
  $out_html .= "</select>
  <script type=\"text/javascript\">
  <!--
  function classe_go()
  {
  box = document.forms[\"classe\"].classe;
  destination = box.options[box.selectedIndex].value;
  if (destination) location.href = destination;
  }
  // -->
  </script>
  <noscript>
  <input type=submit value=\"OK\" />
  </noscript>
  </form>";
  return $out_html;
}

/**
 * Crée un formulaire avec une zone de sélection contenant les groupes 
 * 
 * $id_ref peut être soit l'ID d'une classe, auquel cas on affiche tous les groupes pour la classe,
 * soit le login d'un élève, auquel cas on affiche tous les groupes pour l'élève en question
 * 
 * @param text $link lien vers la page à atteindre ensuite
 * @param int|text $id_ref Id d'une classe ou login d'un élève
 * @param int|text $current Id de la classe courante
 * @param int $year Année
 * @param type $month Mois
 * @param type $day Jour
 * @param text $special
 * @return text La balise <form>
 */
function make_matiere_select_html($link, $id_ref, $current, $year, $month, $day, $special='')
{
	// Pour le multisite, on doit récupérer le RNE de l'établissement
	$prof="";
	
	$rne = isset($_GET['rne']) ? $_GET['rne'] : (isset($_POST['rne']) ? $_POST['rne'] : 'aucun');
	$aff_input_rne = $aff_get_rne = NULL;
	if ($rne != 'aucun') {
		$aff_input_rne = '<input type="hidden" name="rne" value="' . $rne . '" />' . "\n";
		$aff_get_rne = '&amp;rne=' . $rne;
	}
		$out_html = "<form id=\"matiere\"  method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n" . $aff_input_rne . "\n
	<h2 class='h2_label'> \n<label for=\"enseignement\"><strong><em>Matière :<br /></em></strong></label>\n</h2>\n<p>\n<select id=\"enseignement\" name=\"matiere\" onchange=\"matiere_go()\">\n ";
	
	if (is_numeric($id_ref)) {
		$out_html .= "<option value=\"".$link."?&amp;year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;id_classe=$id_ref\">(Choisissez un enseignement)</option>\n";
	
		if($special!='') {
			$selected="";
			if($special=='Toutes_matieres') {$selected=" selected='TRUE'";}
			if (is_numeric($id_ref)) {
				$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;id_classe=$id_ref&amp;id_groupe=Toutes_matieres" . $aff_get_rne;
			} else {
				$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;login_eleve=$id_ref&amp;id_groupe=Toutes_matieres" . $aff_get_rne;
			}
			$out_html .= "<option $selected value=\"$link2\"$selected>Toutes les matières</option>\n";
		}
	
		$sql = "select DISTINCT g.id, g.name, g.description from j_groupes_classes jgc, groupes g, ct_entry ct where (" .
				"jgc.id_classe='".$id_ref."' and " .
				"g.id = jgc.id_groupe and " .
				"jgc.id_groupe = ct.id_groupe" .
				") order by g.name";
	} else {
		$out_html .= "<option value=\"".$link."?&amp;year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;login_eleve=$id_ref\">(Choisissez un enseignement)</option>\n";

		if($special!='') {
			$selected="";
			if($special=='Toutes_matieres') {$selected=" selected='TRUE'";}
			if (is_numeric($id_ref)) {
				$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;id_classe=$id_ref&amp;id_groupe=Toutes_matieres" . $aff_get_rne;
			} else {
				$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;login_eleve=$id_ref&amp;id_groupe=Toutes_matieres" . $aff_get_rne;
			}
			$out_html .= "<option $selected value=\"$link2\"$selected>Toutes les matières</option>\n";
		}

		$sql = "select DISTINCT g.id, g.name, g.description from j_eleves_groupes jec, groupes g, ct_entry ct where (" .
				"jec.login='".$id_ref."' and " .
				"g.id = jec.id_groupe and " .
				"jec.id_groupe = ct.id_groupe" .
				") order by g.name";
	}
	$res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
		$test_prof = "SELECT nom, prenom FROM j_groupes_professeurs j, utilisateurs u WHERE (j.id_groupe='".$row[0]."' and u.login=j.login) ORDER BY nom, prenom";
		$res_prof = sql_query($test_prof);
		$chaine = "";
		for ($k=0;$prof=sql_row($res_prof,$k);$k++) {
			if ($k != 0) $chaine .= ", ";
			$chaine .= htmlspecialchars($prof[0])." ".mb_substr(htmlspecialchars($prof[1]),0,1).".";
		}

		$selected = ($row[0] == $current) ? "selected=\"selected\"" : "";
		if (is_numeric($id_ref)) {
			$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;id_classe=$id_ref&amp;id_groupe=$row[0]" . $aff_get_rne;
		} else {
			$link2 = "$link?&amp;year=$year&amp;month=$month&amp;day=$day&amp;login_eleve=$id_ref&amp;id_groupe=$row[0]" . $aff_get_rne;
		}

		$out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row[2] . " - ")." ".$chaine."</option>";
	}
	$out_html .= "\n</select>\n</p>\n
	
	<script type=\"text/javascript\">
	<!--
	function matiere_go()
	{
		box = document.forms[\"matiere\"].matiere;
		destination = box.options[box.selectedIndex].value;
		if (destination) location.href = destination;
	}
	// -->
	</script>
	
	<noscript><p>\n";
		if (is_numeric($id_ref)) {
			$out_html .= "<input type=\"hidden\" name=\"id_classe\" value=\"$id_ref\" />\n";
		} else {
			$out_html .= "<input type=\"hidden\" name=\"login_eleve\" value=\"$id_ref\" />\n";
		}
		$out_html .= "<input type=\"hidden\" name=\"year\" value=\"$year\" />
		<input type=\"hidden\" name=\"month\" value=\"$month\" />
		<input type=\"hidden\" name=\"day\" value=\"$day\" />
		<input type=\"submit\" value=\"OK\" />
		</p>
	</noscript>
	</form>\n";
	
	return $out_html;
}

/**
 * Crée un formulaire avec une zone de sélection contenant les élèves 
 *
 * @global text 
 * @param text $link lien vers la page à atteindre ensuite
 * @param type $login_resp login du responsable
 * @param type $current login de l'élève actuellement sélectionné
 * @param type $year Année
 * @param type $month Mois
 * @param type $day Jour
 * @return type la balise <form...>
 */
function make_eleve_select_html($link, $login_resp, $current, $year, $month, $day)
{
	global $selected_eleve;
	// $current est le login de l'élève actuellement sélectionné
	$sql="SELECT e.login, e.nom, e.prenom " .
			"FROM eleves e, resp_pers r, responsables2 re " .
			"WHERE (" .
			"e.ele_id = re.ele_id AND " .
			"re.pers_id = r.pers_id AND " .
			"r.login = '".$login_resp."' AND (re.resp_legal='1' OR re.resp_legal='2'));";
	$get_eleves = mysql_query($sql);

	if (mysql_num_rows($get_eleves) == 0) {
			// Aucun élève associé
		$out_html = "<p>Vous semblez n'être responsable d'aucun élève ! Contactez l'administrateur pour corriger cette erreur.</p>";
	} elseif (mysql_num_rows($get_eleves) == 1) {
			// Un seul élève associé : pas de formulaire nécessaire
		$selected_eleve = mysql_fetch_object($get_eleves);
		$out_html = "<p class='bold'>Elève : ".$selected_eleve->prenom." ".$selected_eleve->nom."</p>";
	} else {
		// Plusieurs élèves : on affiche un formulaire pour choisir l'élève
	  $out_html = "<form id=\"eleve\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n<h2 class='h2_label'>\n<label for=\"choix_eleve\"><strong><em>Elève :</em></strong></label>\n</h2>\n<p>\n<select id=\"choix_eleve\" name=\"eleve\" onchange=\"eleve_go()\">\n";
	  $out_html .= "<option value=\"".$link."?year=".$year."&amp;month=".$month."&amp;day=".$day."\">(Choisissez un élève)</option>\n";
		while ($current_eleve = mysql_fetch_object($get_eleves)) {
		   if ($current) {
		   	$selected = ($current_eleve->login == $current->login) ? "selected='selected'" : "";
		   } else {
		   	$selected = "";
		   }
		   $link2 = "$link?year=$year&amp;month=$month&amp;day=$day&amp;login_eleve=".$current_eleve->login;
		   $out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($current_eleve->prenom . " - ".$current_eleve->nom)."</option>\n";
		}
        
	  $out_html .= "</select></p>
	  <script type=\"text/javascript\">
	  <!--
	  function eleve_go()
	  {
	    box = document.forms[\"eleve\"].eleve;
	    destination = box.options[box.selectedIndex].value;
	    if (destination) location.href = destination;
	  }
	  // -->
	  </script>

	  <noscript>
		<p>
		  <input type=\"hidden\" name=\"year\" value=\"$year\" />
		  <input type=\"hidden\" name=\"month\" value=\"$month\" />
		  <input type=\"hidden\" name=\"day\" value=\"$day\" />
		  <input type=\"submit\" value=\"OK\" />
		</p>
		</noscript>
	  </form>\n";
	}
	return $out_html;
}

/**
 * Construit une liste à puces des documents joints
 *
 * @param int $id_ct Id du cahier de texte
 * @param type $type_notice c pour ct_documents, t pour ct_devoirs_documents
 * @return string La liste à puces
 * @see getSettingValue()
 */
function affiche_docs_joints($id_ct,$type_notice) {
  // documents joints
  $html = '';
  $architecture="/documents/cl_dev";
  if ($type_notice == "t") {
      $sql = "SELECT titre, emplacement, visible_eleve_parent  FROM ct_devoirs_documents WHERE id_ct_devoir='$id_ct' ORDER BY 'titre'";
  } else if ($type_notice == "c") {
      $sql = "SELECT titre, emplacement, visible_eleve_parent FROM ct_documents WHERE id_ct='$id_ct' ORDER BY 'titre'";
  }

  $res = sql_query($sql);
    if (($res) and (sql_count($res)!=0)) {
      $html .= "<span class='petit'>Document(s) joint(s):</span>";
      $html .= "<ul style=\"padding-left: 15px;\">";
      for ($i=0; ($row = sql_row($res,$i)); $i++) {
          if((($_SESSION['statut']!='eleve')&&($_SESSION['statut']!='responsable'))||
              ((getSettingValue('cdt_possibilite_masquer_pj')!='y')&&(($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable')))||
              ((getSettingValue('cdt_possibilite_masquer_pj')=='y')&&($row[2]==TRUE)&&(($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable')))
          ) {
                $titre = $row[0];
                $emplacement = $row[1];
              // Ouverture dans une autre fenêtre conservée parce que si le fichier est un PDF, un TXT, un HTML ou tout autre document susceptible de s'ouvrir dans le navigateur, on risque de refermer sa session en croyant juste refermer le document.
              // alternative, utiliser un javascript
                $html .= "<li style=\"padding: 0px; margin: 0px; font-family: arial, sans-serif; font-size: 80%;\"><a onclick=\"window.open(this.href, '_blank'); return FALSE;\" href=\"$emplacement\">$titre</a></li>";
          }
      }
      $html .= "</ul>";
     }
   return $html;
 }

/**
 * Fonction destinée à présenter une liste de liens répartis en $nbcol colonnes
 * 
 *
 * @param type $tab_txt tableau des textes
 * @param type $tab_lien tableau des liens
 * @param int $nbcol Nombre de colonnes
 * @param type $extra_options Options supplémentaires
 */
function tab_liste($tab_txt,$tab_lien,$nbcol,$extra_options = NULL){

	// Nombre d'enregistrements à afficher
	$nombreligne=count($tab_txt);

	if(!is_int($nbcol)){
		$nbcol=3;
	}

	// Nombre de lignes dans chaque colonne:
	$nb_class_par_colonne=round($nombreligne/$nbcol);

	echo "<table width='100%' summary=\"Tableau de choix\">\n";
	echo "<tr valign='top' align='center'>\n";
	echo "<td align='left'>\n";

	$i = 0;
	while ($i < $nombreligne){

		if(($i>0)&&(round($i/$nb_class_par_colonne)==$i/$nb_class_par_colonne)){
			echo "</td>\n";
			echo "<td align='left'>\n";
		}

		//echo "<br />\n";
		echo "<a href='".$tab_lien[$i]."'";
    if ($extra_options) echo ' '.$extra_options;
    echo ">".$tab_txt[$i]."</a>";
		echo "<br />\n";
		$i++;
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

/**
 * Crée des liens html
 *
 * @param string $ele_login Login de l'élève
 * @return string 
 */
function liens_class_from_ele_login($ele_login){
	$chaine="";
	$tab_classe=get_class_from_ele_login($ele_login);
	if(isset($tab_classe)){
		if(count($tab_classe)>0){
			foreach ($tab_classe as $key => $value){
				if(mb_strlen(preg_replace("/[0-9]/","",$key))==0) {
					if($_SESSION['statut']=='administrateur') {
						$chaine.=", <a href='../classes/classes_const.php?id_classe=$key'>$value</a>";
					}
					else {
						$chaine.=", <a href='../eleves/index.php?id_classe=$key&amp;quelles_classes=certaines&amp;case_2=yes'>$value</a>";
					}
				}
			}
			$chaine="(".mb_substr($chaine,2).")";
		}
	}
	return $chaine;
}

/**
 * Teste et construit un tableau html des dossiers qui doivent être accessibles en écriture
 *
 * Par défaut les dossiers nécessaires à GEPI
 * 
 * Le chemin complet doit être passé
 * @global string
 * @param type $tab_restriction Le tableau des répertoires à tester
 */
function test_ecriture_dossier($tab_restriction=array()) {
    global $gepiPath, $multisite;

	if(count($tab_restriction)>0) {
		$tab_dossiers_rw=$tab_restriction;
	}
	else {
		$tab_dossiers_rw=array("artichow/cache","backup","documents","documents/archives","images","images/background","lib/standalone/HTMLPurifier/DefinitionCache/Serializer","mod_ooo/mes_modeles","mod_ooo/tmp","photos","temp");
    /**
     * Pour Debug : Décommenter les 2 lignes si pas en multisites
     */
    /* *
        $multisite='y';
        $_COOKIE['RNE']="essai";
     /* */
        if ((isset($multisite))&&($multisite=='y')&&(isset($_COOKIE['RNE']))) {
          $tab_dossiers_rw[] = 'photos/'.$_COOKIE['RNE'];
          $tab_dossiers_rw[] = 'photos/'.$_COOKIE['RNE'].'/eleves';
          $tab_dossiers_rw[] = 'photos/'.$_COOKIE['RNE'].'/personnels';
        }
	}

	$nom_fichier_test='test_acces_rw';

	echo "<table class='boireaus'>\n";
    echo "<caption style='display:none;'>dossiers devant être accessibles en écriture<caption>\n";
	echo "<tr>\n";
	echo "<th>Dossier</th>\n";
	echo "<th>Ecriture</th>\n";
	echo "</tr>\n";
	$alt=1;
	for($i=0;$i<count($tab_dossiers_rw);$i++) {
		$ok_rw="no";
		if ($f = @fopen("../".$tab_dossiers_rw[$i]."/".$nom_fichier_test, "w")) {
			@fputs($f, '<'.'?php $ok_rw = "yes"; ?'.'>');
			@fclose($f);
			include("../".$tab_dossiers_rw[$i]."/".$nom_fichier_test);
			$del = @unlink("../".$tab_dossiers_rw[$i]."/".$nom_fichier_test);
		}
		$alt=$alt*(-1);
		echo "<tr class='lig$alt white_hover'>\n";
		echo "<td style='text-align:left;'>$gepiPath/$tab_dossiers_rw[$i]</td>\n";
		echo "<td>";
		if($ok_rw=='yes') {
			echo "<img src='../images/enabled.png' height='20' width='20' alt=\"Le dossier est accessible en écriture.\" />";
		}
		else {
			echo "<img src='../images/disabled.png' height='20' width='20' alt=\"Le dossier n'est pas accessible en écriture.\" />";
		}
		echo "</td>\n";
		echo "</tr>\n";

		if($tab_dossiers_rw[$i]=="documents/archives") {
			if($multisite=='y') {
				$dossier_temp='documents/archives/'.$_COOKIE['RNE'];
			}
			else {
				$dossier_temp='documents/archives/etablissement';
			}

			if(file_exists("../$dossier_temp")) {
				$ok_rw="no";
				if ($f = @fopen("../".$dossier_temp."/".$nom_fichier_test, "w")) {
					@fputs($f, '<'.'?php $ok_rw = "yes"; ?'.'>');
					@fclose($f);
					include("../".$dossier_temp."/".$nom_fichier_test);
					$del = @unlink("../".$dossier_temp."/".$nom_fichier_test);
				}
				$alt=$alt*(-1);
				echo "<tr class='lig$alt white_hover'>\n";
				echo "<td style='text-align:left;'>$gepiPath/$dossier_temp</td>\n";
				echo "<td>";
				if($ok_rw=='yes') {
					echo "<img src='../images/enabled.png' height='20' width='20' alt=\"Le dossier est accessible en écriture.\" />";
				}
				else {
					echo "<img src='../images/disabled.png' height='20' width='20' alt=\"Le dossier n'est pas accessible en écriture.\" />";
				}
				echo "</td>\n";
				echo "</tr>\n";

			}
		}
	}
	echo "</table>\n";
}

/**
 * Affiche un tableau html des connexions d'un utilisateur
 * 
 * $duree
 * -   7 -> "une semaine"
 * -  15 -> "quinze jours"
 * -  30 -> "un mois"
 * -  60 -> "deux mois"
 * - 183 -> "six mois"
 * - 365 -> "un an"
 * - all -> "le début"
 *
 * @param string $login Login de l'utilisateur
 * @param int|string $duree
 * @param string $page 'mon_compte' pour afficher ses propres connexions
 * @param string $pers_id Permet d'avoir une balise <input type='hidden' personnelle
 * @todo On pourrait utiliser $_SESSION['login'] plutôt que $page
 */
function journal_connexions($login,$duree,$page='mon_compte',$pers_id=NULL) {
	switch( $duree ) {
	case 7:
		$display_duree="une semaine";
		break;
	case 15:
		$display_duree="quinze jours";
		break;
	case 30:
		$display_duree="un mois";
		break;
	case 60:
		$display_duree="deux mois";
		break;
	case 183:
		$display_duree="six mois";
		break;
	case 365:
		$display_duree="un an";
		break;
	case 'all':
		$display_duree="le début";
		break;
	}

	if($page=='mon_compte') {
		echo "<h2>Journal de vos connexions depuis <strong>".$display_duree."</strong>**</h2>\n";
	}
	else {
		echo "<h2>Journal des connexions de ".civ_nom_prenom($login)." depuis <strong>".$display_duree."</strong>**</h2>\n";
	}
	$requete = '';
	if ($duree != 'all') {$requete = "and START > now() - interval " . $duree . " day";}

	$sql = "select START, SESSION_ID, REMOTE_ADDR, USER_AGENT, AUTOCLOSE, END from log where LOGIN = '".$login."' ".$requete." order by START desc";
	//echo "$sql<br />";
	$day_now   = date("d");
	$month_now = date("m");
	$year_now  = date("Y");
	$hour_now  = date("H");
	$minute_now = date("i");
	$seconde_now = date("s");
	$now = mktime($hour_now, $minute_now, $seconde_now, $month_now, $day_now, $year_now);

	echo "<ul>
<li>Les lignes en <span style='color:red; font-weight:bold;'>rouge</span> signalent une tentative de connexion avec un <span style='color:red; font-weight:bold;'>mot de passe erroné</span>.</li>
<li>Les lignes en <span style='color:orange; font-weight:bold;'>orange</span> signalent une session close pour laquelle vous ne vous êtes <span style='color:orange; font-weight:bold;'>pas déconnecté correctement</span>.</li>
<li>Les lignes en <span style='color:black; font-weight:bold;'>noir</span> signalent une <span style='color:black; font-weight:bold;'>session close normalement</span>.</li>
<li>Les lignes en <span style='color:green; font-weight:bold;'>vert</span> indiquent les <span style='color:green; font-weight:bold;'>sessions en cours</span> (<em>cela peut correspondre à une connexion actuellement close mais pour laquelle vous ne vous êtes pas déconnecté correctement</em>).</li>
</ul>
<table class='col' style='width: 90%; margin-left: auto; margin-right: auto; margin-bottom: 32px;' cellpadding='5' cellspacing='0' summary='Connexions'>
	<tr>
		<th class='col'>Début session</th>
		<th class='col'>Fin session</th>
		<th class='col'>Adresse IP et nom de la machine cliente</th>
		<th class='col'>Navigateur</th>
	</tr>
";

	$res = sql_query($sql);
	if ($res) {
		for ($i = 0; ($row = sql_row($res, $i)); $i++)
		{
			$annee_b = mb_substr($row[0],0,4);
			$mois_b =  mb_substr($row[0],5,2);
			$jour_b =  mb_substr($row[0],8,2);
			$heures_b = mb_substr($row[0],11,2);
			$minutes_b = mb_substr($row[0],14,2);
			$secondes_b = mb_substr($row[0],17,2);
			$date_debut = $jour_b."/".$mois_b."/".$annee_b." à ".$heures_b." h ".$minutes_b;

			$annee_f = mb_substr($row[5],0,4);
			$mois_f =  mb_substr($row[5],5,2);
			$jour_f =  mb_substr($row[5],8,2);
			$heures_f = mb_substr($row[5],11,2);
			$minutes_f = mb_substr($row[5],14,2);
			$secondes_f = mb_substr($row[5],17,2);
			$date_fin = $jour_f."/".$mois_f."/".$annee_f." à ".$heures_f." h ".$minutes_f;
			$end_time = mktime($heures_f, $minutes_f, $secondes_f, $mois_f, $jour_f, $annee_f);

			$temp1 = '';
			$temp2 = '';
			if ($end_time > $now) {
				$temp1 = "<font color='green'>";
				$temp2 = "</font>";
			} else if (($row[4] == 1) or ($row[4] == 2) or ($row[4] == 3) or ($row[4] == 10)) {
				//$temp1 = "<font color=orange>\n";
				$temp1 = "<font color='#FFA500'>";
				$temp2 = "</font>";
			} else if ($row[4] == 4) {
				$temp1 = "<strong><font color='red'>";
				$temp2 = "</font></strong>";

			}

			echo "<tr>\n";
			echo "<td class=\"col\">".$temp1.$date_debut.$temp2."</td>\n";
			if ($row[4] == 2) {
				echo "<td class=\"col\">".$temp1."Tentative de connexion<br />avec mot de passe erroné.".$temp2."</td>\n";
			}
			else {
				echo "<td class=\"col\">".$temp1.$date_fin.$temp2."</td>\n";
			}
			if (!(isset($active_hostbyaddr)) or ($active_hostbyaddr == "all")) {
				$result_hostbyaddr = " - ".@gethostbyaddr($row[2]);
			}
			else if ($active_hostbyaddr == "no_local") {
				if ((mb_substr($row[2],0,3) == 127) or
					(mb_substr($row[2],0,3) == 10.) or
					(mb_substr($row[2],0,7) == 192.168)) {
					$result_hostbyaddr = "";
				}
				else {
					$tabip=explode(".",$row[2]);
					if(($tabip[0]==172)&&($tabip[1]>=16)&&($tabip[1]<=31)) {
						$result_hostbyaddr = "";
					}
					else {
						$result_hostbyaddr = " - ".@gethostbyaddr($row[2]);
					}
				}
			}
			else {
				$result_hostbyaddr = "";
			}

			echo "<td class=\"col\"><span class='small'>".$temp1.$row[2].$result_hostbyaddr.$temp2. "</span></td>\n";
			echo "<td class=\"col\">".$temp1. detect_browser($row[3]) .$temp2. "</td>\n";
			echo "</tr>\n";
			flush();
		}
	}


	echo "</table>\n";

	echo "<form action=\"".$_SERVER['PHP_SELF']."#connexion\" name=\"form_affiche_log\" method=\"post\">\n";

	if($page=='modify_user') {
		echo "<input type='hidden' name='user_login' value='$login' />\n";
		echo "<input type='hidden' name='journal_connexions' value='y' />\n";
	}
	elseif($page=='modify_eleve') {
		echo "<input type='hidden' name='eleve_login' value='$login' />\n";
		echo "<input type='hidden' name='journal_connexions' value='y' />\n";
	}
	elseif($page=='modify_resp') {
		echo "<input type='hidden' name='pers_id' value='$pers_id' />\n";
		echo "<input type='hidden' name='journal_connexions' value='y' />\n";
	}

	echo "Afficher le journal des connexions depuis : <select name=\"duree\" size=\"1\">\n";
	echo "<option ";
	if ($duree == 7) echo "selected";
	echo " value=7>Une semaine</option>\n";
	echo "<option ";
	if ($duree == 15) echo "selected";
	echo " value=15 >Quinze jours</option>\n";
	echo "<option ";
	if ($duree == 30) echo "selected";
	echo " value=30>Un mois</option>\n";
	echo "<option ";
	if ($duree == 60) echo "selected";
	echo " value=60>Deux mois</option>\n";
	echo "<option ";
	if ($duree == 183) echo "selected";
	echo " value=183>Six mois</option>\n";
	echo "<option ";
	if ($duree == 365) echo "selected";
	echo " value=365>Un an</option>\n";
	echo "<option ";
	if ($duree == 'all') echo "selected";
	echo " value='all'>Le début</option>\n";
	echo "</select>\n";
	echo "<input type=\"submit\" name=\"Valider\" value=\"Valider\" />\n";

	echo "</form>\n";

	echo "<p class='small'>** Les renseignements ci-dessus peuvent vous permettre de vérifier qu'une connexion pirate n'a pas été effectuée sur votre compte.
	Dans le cas d'une connexion inexpliquée, vous devez immédiatement en avertir l'<a href=\"mailto:" . getSettingValue("gepiAdminAdress") . "\">administrateur</a>.</p>\n";
}

/**
 * Affiche une action à effectuer
 */
function affiche_infos_actions() {
	$sql="SELECT ia.* FROM infos_actions ia, infos_actions_destinataires iad WHERE
	ia.id=iad.id_info AND
	((iad.nature='individu' AND iad.valeur='".$_SESSION['login']."') OR
	(iad.nature='statut' AND iad.valeur='".$_SESSION['statut']."'));";
    
	$res=mysql_query($sql);
	$chaine_id="";
	if(mysql_num_rows($res)>0) {
		echo "<div id='div_infos_actions' style='width: 60%; border: 2px solid red; padding:3px; margin-left: 20%;'>\n";
		echo "<div id='info_action_titre' style='font-weight: bold;' class='infobulle_entete'>\n";
			echo "<div id='info_action_pliage' style='float:right; width: 1em'>\n";
			echo "<a href=\"javascript:div_alterne_affichage('conteneur')\"><span id='img_pliage_conteneur'><img src='images/icons/remove.png' width='16' height='16' /></span></a>";
			echo "</div>\n";
			echo "Actions en attente";
		echo "</div>\n";

		echo "<div id='info_action_corps_conteneur'>\n";

		$cpt_id=0;
		while($lig=mysql_fetch_object($res)) {
			echo "<div id='info_action_$lig->id' style='border: 1px solid black; margin:2px;'>\n";
				echo "<div id='info_action_titre_$lig->id' style='font-weight: bold;' class='infobulle_entete'>\n";
					echo "<div id='info_action_pliage_$lig->id' style='float:right; width: 1em'>\n";
					echo "<a href=\"javascript:div_alterne_affichage('$lig->id')\"><span id='img_pliage_$lig->id'><img src='images/icons/remove.png' width='16' height='16' /></span></a>";
					echo "</div>\n";
					echo $lig->titre;
				echo "</div>\n";

				echo "<div id='info_action_corps_$lig->id' style='padding:3px;' class='infobulle_corps'>\n";
					echo "<div style='float:right; width: 9em; text-align: right;'>\n";
					echo "<a href=\"".$_SERVER['PHP_SELF']."?del_id_info=$lig->id".add_token_in_url()."\" onclick=\"return confirmlink(this, '".traitement_magic_quotes($lig->titre)."', 'Etes-vous sûr de vouloir supprimer ".traitement_magic_quotes($lig->titre)."')\">Supprimer</span></a>";
					echo "</div>\n";

					echo nl2br($lig->description);
				echo "</div>\n";
			echo "</div>\n";
			if($cpt_id>0) {$chaine_id.=", ";}
			$chaine_id.="'$lig->id'";
			$cpt_id++;
		}
		echo "</div>\n";
		echo "</div>\n";

		echo "<script type='text/javascript'>
	function div_alterne_affichage(id) {
		if(document.getElementById('info_action_corps_'+id)) {
			if(document.getElementById('info_action_corps_'+id).style.display=='none') {
				document.getElementById('info_action_corps_'+id).style.display='';
				document.getElementById('img_pliage_'+id).innerHTML='<img src=\'images/icons/remove.png\' width=\'16\' height=\'16\' />'
			}
			else {
				document.getElementById('info_action_corps_'+id).style.display='none';
				document.getElementById('img_pliage_'+id).innerHTML='<img src=\'images/icons/add.png\' width=\'16\' height=\'16\' />'
			}
		}
	}

	chaine_id_action=new Array($chaine_id);
	for(i=0;i<chaine_id_action.length;i++) {
		id_a=chaine_id_action[i];
		if(document.getElementById('info_action_corps_'+id_a)) {
			div_alterne_affichage(id_a);
		}
	}
</script>\n";
	}
}


/**
 * Affiche les accès aux cahiers de texte
 */
function affiche_acces_cdt() {
	$retour="";

	$tab_statuts=array('professeur', 'administrateur', 'scolarite');
	if(in_array($_SESSION['statut'], $tab_statuts)) {
		$sql="SELECT a.* FROM acces_cdt a ORDER BY date2;";
		//echo "$sql<br />";
		$res=mysql_query($sql);
		$chaine_id="";
		if(mysql_num_rows($res)>0) {
			$visible="y";
			if($_SESSION['statut']=='professeur') {
				$visible="n";
				$sql="SELECT ag.id_acces FROM acces_cdt_groupes ag, j_groupes_professeurs jgp WHERE jgp.id_groupe=ag.id_groupe AND jgp.login='".$_SESSION['login']."';";
				$res2=mysql_query($sql);
				if(mysql_num_rows($res2)>0) {
					$visible="y";
					$tab_id_acces=array();
					while($lig2=mysql_fetch_object($res2)) {
						$tab_id_acces[]=$lig2->id_acces;
					}
				}
			}
	
			if($visible=="y") {
				$retour.="<div id='div_infos_acces_cdt' style='width: 60%; border: 2px solid red; padding:3px; margin-left: 20%; margin-top:3px;'>\n";
				$retour.="<div id='info_acces_cdt_titre' style='font-weight: bold;' class='infobulle_entete'>\n";
					$retour.="<div id='info_acces_cdt_pliage' style='float:right; width: 1em'>\n";
					$retour.="<a href=\"javascript:div_alterne_affichage_acces_cdt('conteneur')\"><span id='img_pliage_acces_cdt_conteneur'><img src='images/icons/remove.png' width='16' height='16' /></span></a>";
					$retour.="</div>\n";
					$retour.="Accès ouvert à des CDT";
				$retour.="</div>\n";
		
				$retour.="<div id='info_acces_cdt_corps_conteneur'>\n";
		
				$cpt_id=0;
				while($lig=mysql_fetch_object($res)) {
					$visible="y";
					if(($_SESSION['statut']=='professeur')&&(!in_array($lig->id,$tab_id_acces))) {
						$visible="n";
					}
	
					if($visible=="y") {
						$retour.="<div id='info_acces_cdt_$lig->id' style='border: 1px solid black; margin:2px;'>\n";
							$retour.="<div id='info_acces_cdt_titre_$lig->id' style='font-weight: bold;' class='infobulle_entete'>\n";
								$retour.="<div id='info_acces_cdt_pliage_$lig->id' style='float:right; width: 1em'>\n";
								$retour.="<a href=\"javascript:div_alterne_affichage_acces_cdt('$lig->id')\"><span id='img_pliage_acces_cdt_$lig->id'><img src='images/icons/remove.png' width='16' height='16' /></span></a>";
								$retour.="</div>\n";
								$retour.="Accès CDT jusqu'au ".formate_date($lig->date2);
							$retour.="</div>\n";
			
							$retour.="<div id='info_acces_cdt_corps_$lig->id' style='padding:3px;' class='infobulle_corps'>\n";
								if(($_SESSION['statut']=='administrateur')||($_SESSION['statut']=='scolarite')) {
									$retour.="<div style='float:right; width: 9em; text-align: right;'>\n";
									$retour.="<a href=\"".$_SERVER['PHP_SELF']."?del_id_acces_cdt=$lig->id".add_token_in_url()."\" onclick=\"return confirmlink(this, '".traitement_magic_quotes($lig->description)."', 'Etes-vous sûr de vouloir supprimer cet accès')\">Supprimer l'accès</span></a>";
									$retour.="</div>\n";
								}
	
								$retour.="<p><strong>L'accès a été ouvert pour le motif suivant&nbsp;:</strong><br />";
								$retour.=preg_replace("/\\\\r\\\\n/","<br />",$lig->description);
								$retour.="</p>\n";
	
								$chaine_enseignements="<ul>";
								$sql="SELECT id_groupe FROM acces_cdt_groupes WHERE id_acces='$lig->id';";
								$res3=mysql_query($sql);
								if(mysql_num_rows($res3)>0) {
									$tab_champs=array('classes', 'professeurs');
									while($lig3=mysql_fetch_object($res3)) {
										$current_group=get_group($lig3->id_groupe);
	
										$chaine_profs="";
										$cpt=0;
										foreach($current_group['profs']['users'] as $login_prof => $current_prof) {
											if($cpt>0) {$chaine_profs.=", ";}
											$chaine_profs.=$current_prof['civilite']." ".$current_prof['nom']." ".$current_prof['prenom'];
											$cpt++;
										}
	
										$chaine_enseignements.="<li>";
										$chaine_enseignements.=$current_group['name']." (<i>".$current_group['description']."</i>) en ".$current_group['classlist_string']." (<i>".$chaine_profs."</i>)";
										$chaine_enseignements.="</li>\n";
									}
								}
								$chaine_enseignements.="</ul>";
								
								$retour.="<p>Les CDT accessibles à l'adresse <a href='$lig->chemin' target='_blank'>$lig->chemin</a> sont&nbsp;:<br />".$chaine_enseignements."</p>";
							$retour.="</div>\n";
						$retour.="</div>\n";
						if($cpt_id>0) {$chaine_id.=", ";}
						$chaine_id.="'$lig->id'";
						$cpt_id++;
					}
				}
				$retour.="</div>\n";
				$retour.="</div>\n";
		
				$retour.="<script type='text/javascript'>
			function div_alterne_affichage_acces_cdt(id) {
				if(document.getElementById('info_acces_cdt_corps_'+id)) {
					if(document.getElementById('info_acces_cdt_corps_'+id).style.display=='none') {
						document.getElementById('info_acces_cdt_corps_'+id).style.display='';
						document.getElementById('img_pliage_acces_cdt_'+id).innerHTML='<img src=\'images/icons/remove.png\' width=\'16\' height=\'16\' />'
					}
					else {
						document.getElementById('info_acces_cdt_corps_'+id).style.display='none';
						document.getElementById('img_pliage_acces_cdt_'+id).innerHTML='<img src=\'images/icons/add.png\' width=\'16\' height=\'16\' />'
					}
				}
			}
		
			chaine_id_acces_cdt=new Array($chaine_id);
			for(i=0;i<chaine_id_acces_cdt.length;i++) {
				id_a=chaine_id_acces_cdt[i];
				if(document.getElementById('info_acces_cdt_corps_'+id_a)) {
					div_alterne_affichage_acces_cdt(id_a);
				}
			}
		</script>\n";
			}
		}
	}
	echo $retour;
}

/**
 * Crée une balise <p> avec les actions possibles sur un compte
 *
 * @global string 
 * @param string $login Id de l'utilisateur
 * @return string La balises
 * @see add_token_in_url()
 */
function affiche_actions_compte($login) {
	global $gepiPath;

	$retour="";

	$user=get_infos_from_login_utilisateur($login);

	$retour.="<p>\n";
	if ($user['etat'] == "actif") {
		$retour.="<a style='padding: 2px;' href='$gepiPath/gestion/security_panel.php?action=desactiver&amp;afficher_les_alertes_d_un_compte=y&amp;user_login=".$login;
		$retour.=add_token_in_url()."'>Désactiver le compte</a>";
	} else {
		$retour.="<a style='padding: 2px;' href='$gepiPath/gestion/security_panel.php?action=activer&amp;afficher_les_alertes_d_un_compte=y&amp;user_login=".$login;
		$retour.=add_token_in_url()."'>Réactiver le compte</a>";
	}
	$retour.="<br />\n";
	if ($user['observation_securite'] == 0) {
		$retour.="<a style='padding: 2px;' href='$gepiPath/gestion/security_panel.php?action=observer&amp;afficher_les_alertes_d_un_compte=y&amp;user_login=".$login;
		$retour.=add_token_in_url()."'>Placer en observation</a>";
	} else {
		$retour.="<a style='padding: 2px;' href='$gepiPath/gestion/security_panel.php?action=stop_observation&amp;afficher_les_alertes_d_un_compte=y&amp;user_login=".$login;
		$retour.=add_token_in_url()."'>Retirer l'observation</a>";
	}
	if($user['niveau_alerte']>0) {
		$retour.="<br />\n";
		$retour.="Score cumulé&nbsp;: ".$user['niveau_alerte'];
		$retour.="<br />\n";
		$retour.="<a style='padding: 2px;' href='$gepiPath/gestion/security_panel.php?action=reinit_cumul&amp;afficher_les_alertes_d_un_compte=y&amp;user_login=".$login;
		$retour.=add_token_in_url()."'>Réinitialiser cumul</a>";
	}
	$retour.="</p>\n";

	return $retour;
}

/**
 * Insère une fonction javascript pour passer en gras/normal le label associé à un champ checkbox
 *
 * @param string $nom_js_func le nom de la fonction javascript (par défaut 'checkbox_change')
 * @param string $prefixe_texte le préfixe de l'id du label associé (par défaut 'texte_')
 *               Si l'id du checkbox est id_groupe_12, le label doit avoir l'id texte_id_groupe_12
 * @param string $avec_balise_script 'n': On ne renvoye que le texte de la fonction
 *                                   'y': On renvoye le texte entre balises <script>
 * Sur les checkbox, insérer onchange="checkbox_change(this.id)"
 * @return string Le texte de la fonction javascript
 */
function js_checkbox_change_style($nom_js_func='checkbox_change', $prefixe_texte='texte_', $avec_balise_script="n") {
	$retour="";
	if($avec_balise_script!="n") {$retour.="<script type='text/javascript'>\n";}
	$retour.="
	function $nom_js_func(id) {
		if(document.getElementById(id)) {
			if(document.getElementById('$prefixe_texte'+id)) {
				if(document.getElementById(id).checked) {
					document.getElementById('$prefixe_texte'+id).style.fontWeight='bold';
				}
				else {
					document.getElementById('$prefixe_texte'+id).style.fontWeight='normal';
				}
			}
		}
	}\n";
	if($avec_balise_script!="n") {$retour.="</script>\n";}
	return $retour;
}

$tableau_type_login=array('name', 'name8', 'fname8', 'fname19', 'firstdotname', 'firstdotname19', 'namef8', 'lcs', 'name9_p', 'p_name9', 'name9-p', 'p-name9', 'name9.p', 'p.name9', 'name9_ppp', 'ppp_name9', 'name9-ppp', 'ppp-name9', 'name9.ppp', 'ppp.name9');
$tableau_type_login_description=array('nom', 
'nom (<em>tronqué à 8 caractères</em>)', 
'pnom (<em>tronqué à 8 caractères</em>)', 
'pnom (<em>tronqué à 19 caractères</em>)', 
'prenom.nom', 
'prenom.nom (<em>tronqué à 19 caractères</em>)', 
'nomp (<em>tronqué à 8 caractères</em>)', 
'pnom (<em>façon LCS</em>)', 
'nom tronqué à 9 caractères + _ + initiale du prénom', 
'initiale du prénom + _ + nom tronqué à 9 caractères', 
'nom tronqué à 9 caractères + - + initiale du prénom', 
'initiale du prénom + - + nom tronqué à 9 caractères', 
'nom tronqué à 9 caractères +  + initiale du prénom', 
'initiale du prénom + . + nom tronqué à 9 caractères', 
'nom tronqué à 9 caractères + _ + 3 premiers caractères du prénom', 
'3 premiers caractères du prénom + _ + nom tronqué à 9 caractères', 
'nom tronqué à 9 caractères + - + 3 premiers caractères du prénom', 
'3 premiers caractères du prénom + - + nom tronqué à 9 caractères', 
'nom tronqué à 9 caractères + . + 3 premiers caractères du prénom', 
'3 premiers caractères du prénom + . + nom tronqué à 9 caractères');

/**
 * Insère les champs radio de choix du format de login
 * @param string $nom_champ : le nom du champ de formulaire
 * @param string $default_login_gen_type : le format par défaut
 * @return string Les champs radio
 */
function champs_radio_choix_format_login($nom_champ, $default_login_gen_type="name") {
	global $tableau_type_login, $tableau_type_login_description;

	$retour="";

	for($i=0;$i<count($type_login);$i++) {
		$retour.="<input type='radio' name='".$nom_champ."' id='".$nom_champ."_".$tableau_type_login[$i]."' value='".$tableau_type_login[$i]."' ";
		if($default_login_gen_type==$tableau_type_login[$i]) {
			$retour.="checked ";
		}
		$retour.=" onchange='changement()'";
		$retour.="/> <label for='".$nom_champ."_".$tableau_type_login[$i]."'  style='cursor: pointer;'>".$tableau_type_login_description[$i]."</label>\n";
		$retour.="<br />\n";
	}

	if (getSettingValue("use_ent") == "y") {
		$retour.="<input type='radio' name='".$nom_champ."' id='".$nom_champ."_ent' value='ent' checked=\"checked\" ";
		$retour.="onchange='changement()' ";
		$retour.="/>\n";
		$retour.="<label for='".$nom_champ."_ent'  style='cursor: pointer;'>
			Les logins sont produits par un ENT (<span title=\"Vous devez adapter le code du fichier ci-dessus vers la ligne 710.\">Attention !</span>)</label>\n";
		$retour.="<br />\n";
	}
	$retour.="<br />\n";

	return $retour;
}

/**
 * Insère le champ select de choix du format de login
 * @param string $nom_champ : le nom du champ de formulaire
 * @param string $default_login_gen_type : le format par défaut
 * @return string Le champ select avec ses options
 */
function champs_select_choix_format_login($nom_champ, $default_login_gen_type="name") {
	global $tableau_type_login, $tableau_type_login_description;

	$retour="<select name='".$nom_champ."' onchange='changement()'>\n";

	for($i=0;$i<count($tableau_type_login);$i++) {
		$retour.="<option value='".$tableau_type_login[$i]."'";
		if($default_login_gen_type==$tableau_type_login[$i]) {
			$retour.=" selected='true'";
		}
		$retour.=" onchange='changement()'";
		$retour.=">".$tableau_type_login_description[$i]."</option>\n";
	}

	$retour.="</select>\n";

	return $retour;
}


/**
 * Insère un champ input de choix du format de login
 * @param string $nom_champ : le nom du champ de formulaire
 * @param string $default_login_gen_type : le format par défaut
 * @return string Le champ input
*/
function champ_input_choix_format_login($nom_champ, $default_login_gen_type="nnnnnnnnnnnnnnnnnnnn", $longueur_champ=20, $longueur_max_champ=48, $avec_infobulle_explication='y') {
	global $posDiv_infobulle;
	global $tabid_infobulle;
	global $unite_div_infobulle;
	global $niveau_arbo;
	global $pas_de_decalage_infobulle;
	global $class_special_infobulle;
	global $tabdiv_infobulle;

	$retour="<input type='text' name='$nom_champ' id='$nom_champ' value='$default_login_gen_type' size='$longueur_champ' maxlength='$longueur_max_champ' />\n";

	if($avec_infobulle_explication=='y') {
		$titre_infobulle="Formats de login";

		$texte_infobulle="<h4>Contraintes sur le format</h4>
<ul>
	<li>Au maximum 48 caractères.</li>
	<li>Au moins une lettre du prénom et une lettre du nom (<em>quel que soit l'ordre</em>).</li>
	<li>Un caractère entre le prénom et le nom parmi \"<strong>.-_</strong>\", ou aucun.</li>
</ul>
<hr />
<h4>Méthode employée</h4>
<p>
	Le modèle est indiqué à l'aide d'une suite de caractères.<br />
	Exemples pour un utilisateur se nommant <strong>Jean Aimarre</strong>.
</p>
<ul>
	<li>\"<strong>ppp.nnnnnnnn</strong>\" donnera \"<strong>jea.aimarre</strong>\"</li>

	<li>\"<strong>ppp-nnn</strong>\" donnera \"<strong>jea-aim</strong>\"</li>
	<li>\"<strong>p_nnnnnnnnnnn</strong>\" donnera \"<strong>j_aimarre</strong>\"</li>
	<li>\"<strong>pnnnnn</strong>\" donnera \"<strong>jaimar</strong>\"</li>

	<li>\"<strong>nnnnnnnnp</strong>\" donnera \"<strong>aimarrej</strong>\"</li>
	<li>\"<strong>n.ppp</strong>\" donnera \"<strong>a.jea</strong>\"</li>
</ul>\n";

		$tabdiv_infobulle[]=creer_div_infobulle('div_explication_formats_login_'.$nom_champ,$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');
		//$retour.=creer_div_infobulle('div_explication_formats_login',$titre_infobulle,"",$texte_infobulle,"",35,0,'y','y','n','n');

		$retour.=" <a href='#' onclick=\"afficher_div('div_explication_formats_login_$nom_champ','y',20,20); return false\" onmouseover=\"delais_afficher_div('div_explication_formats_login_$nom_champ','y',20,20,1000,20,20)\" onmouseout=\"cacher_div('div_explication_formats_login_.$nom_champ')\"><img src='../images/icons/ico_question_petit.png' width='15' height='15' /></a>";
	}

	$retour.="<br />\n";
	$retour.="Casse du login&nbsp;: <label for='".$nom_champ."_casse_min'>minuscules</label><input type='radio' name='".$nom_champ."_casse' id='".$nom_champ."_casse_min' value='min' ";
	if((getSettingValue($nom_champ.'_casse')=='min')||(getSettingValue($nom_champ.'_casse')=='')) {$retour.="checked ";}
	$retour.="/> / \n";
	$retour.="<input type='radio' name='".$nom_champ."_casse' id='".$nom_champ."_casse_maj' value='maj' ";
	if(getSettingValue($nom_champ.'_casse')=='maj') {$retour.="checked ";}
	$retour.="/><label for='".$nom_champ."_casse_maj'> majuscules</label>\n";

	return $retour;
}

/**
 * Test du format de login proposé
 * @param string $format_login : le format de login proposé
 * @return boolean : true/false selon que le format est valide ou non
*/
function check_format_login($format_login) {
	if($format_login=="") {
		$test_profil = false;
	}
	elseif(mb_strlen($format_login)>48) {
		$test_profil = false;
	}
	else {
		$test_profil = (preg_match("#^p*[._-]?n*$#", $format_login)) ? true : false;
		$test_profil = (preg_match("#^n*[._-]?p*$#", $format_login)) ? true : $test_profil;
	}
	return $test_profil;
}

/**
 * Test JavaScript du format de login proposé pour vérification avant submit
 * @param string $nom_js_func : le nom de la fonction JS à insérer
 * @param string $avec_balise_script 'n': On ne renvoye que le texte de la fonction
 *                                   'y': On renvoye le texte entre balises <script>
 * @return boolean : true/false selon que le format est valide ou non
*/
function insere_js_check_format_login($nom_js_func, $avec_balise_script="n") {
	$retour="";
	if($avec_balise_script!="n") {$retour.="<script type='text/javascript'>\n";}
	$retour.="
	function $nom_js_func(format) {
		if((format=='')||(format.length>48)) {
			test = false;
		}
		else {
			var reg1 = new RegExp(\"^p*[._-]?n*$\",\"g\");
			var reg2 = new RegExp(\"^n*[._-]?p*$\",\"g\");
			test = ( reg1.test(format) || reg2.test(format) ) ? true : false ;
		}
		return test;
	}\n";
	if($avec_balise_script!="n") {$retour.="</script>\n";}
	return $retour;
}

function check_param_bloc_adresse_html($a4_ou_a3="a4") {
	if($a4_ou_a3=="a4") {
		$largeur_page=210;
		$hauteur_page=297;
	}
	else {
		$largeur_page=297;
		$hauteur_page=420;
	}
	
	$retour="";
	
	$temoin_erreur="n";
	
	$addressblock_padding_right=getSettingValue('addressblock_padding_right');
	if(($addressblock_padding_right=='')||(!preg_match('/^[0-9]*$/',$addressblock_padding_right))) {
		$retour.="La valeur de l'espace à droite du bloc adresse n'est pas valide&nbsp;: '$addressblock_padding_right'<br />";
		$temoin_erreur="y";
	}
	
	$addressblock_length=getSettingValue('addressblock_length');
	if(($addressblock_length=='')||(!preg_match('/^[0-9]*$/',$addressblock_length))) {
		$retour.="La valeur de la longueur du bloc adresse n'est pas valide&nbsp;: '$addressblock_length'<br />";
		$temoin_erreur="y";
	}
	
	if($temoin_erreur!="y") {
		if($addressblock_padding_right+$addressblock_length>=$largeur_page) {
			$retour.="La somme des longueur du bloc adresse et de l'espace à droite du bloc adresse dépasse la largeur de la page&nbsp;: $addressblock_padding_right + $addressblock_length >= $largeur_page<br />";
		}
	}
	
	$temoin_erreur="n";
	
	$addressblock_padding_top=getSettingValue('addressblock_padding_top');
	$addressblock_padding_text=getSettingValue('addressblock_padding_text');
	if(($addressblock_padding_top=='')||(!preg_match('/^[0-9]*$/',$addressblock_padding_top))) {
		$retour.="La valeur de l'espace au-dessus du bloc adresse n'est pas valide&nbsp;: '$addressblock_padding_top'<br />";
		$temoin_erreur="y";
	}
	
	if(($addressblock_padding_text=='')||(!preg_match('/^[0-9]*$/',$addressblock_padding_text))) {
		$retour.="La valeur de l'espace au-dessous du bloc adresse n'est pas valide&nbsp;: '$addressblock_padding_text'<br />";
		$temoin_erreur="y";
	}
	
	if($temoin_erreur!="y") {
		if($addressblock_padding_top+$addressblock_padding_text>=$hauteur_page) {
			$retour.="La somme des espaces au-dessus et au-dessous du bloc adresse dépassent la hauteur de la page&nbsp;: $addressblock_padding_top + $addressblock_padding_text >= $hauteur_page<br />";
		}
	}
	
	// Pourcentages:
	$addressblock_logo_etab_prop=getSettingValue('addressblock_logo_etab_prop');
	if(($addressblock_logo_etab_prop=='')||(!preg_match('/^[0-9]*$/',$addressblock_logo_etab_prop))) {
		$retour.="La valeur de la proportion horizontale allouée au logo de l'établissement n'est pas valide&nbsp;: '$addressblock_logo_etab_prop'<br />";
	}
	elseif($addressblock_logo_etab_prop>100) {
		$retour.="La valeur de la proportion horizontale allouée au logo de l'établissement dépasse les 100%&nbsp;: '$addressblock_logo_etab_prop'<br />";
	}
	
	$addressblock_classe_annee=getSettingValue('addressblock_classe_annee');
	if(($addressblock_classe_annee=='')||(!preg_match('/^[0-9]*$/',$addressblock_classe_annee))) {
		$retour.="La valeur de la proportion horizontale allouée au bloc 'Classe, année, période' n'est pas valide&nbsp;: '$addressblock_classe_annee'<br />";
	}
	elseif($addressblock_classe_annee>100) {
		$retour.="La valeur de la proportion horizontale allouée au bloc 'Classe, année, période' n'est pas valide&nbsp;: '$addressblock_classe_annee'<br />";
	}
	
	return $retour;
}

/**
 * Insertion d'un lien destiné à provoquer l'insertion du code <img src...>
 * vers l'url de l'image passée en paramètre.
  *
 * @param string $url_img : Url de l'image
 *
 * @return string : Chaine HTML <div float:right...><a href...><img...></a></div>
*/
function insere_lien_insertion_image_dans_ckeditor($url_img) {
	$tmp_largeur='';
	$tmp_hauteur='';
	$tmp_size = getimagesize($url_img);
	if($tmp_size) {
		// Pour que l'image puisse être redimensionnée facilement dans la page, on fixe la largeur max à 400px
		$tmp_largeur_max=400;

		if(($tmp_size[0]>$tmp_largeur_max)&&($tmp_size[1]>20)) {
			$tmp_largeur=$tmp_largeur_max;
			$tmp_hauteur=round($tmp_size[1]*$tmp_largeur_max/$tmp_size[0]);
		}
		else {
			$tmp_largeur=$tmp_size[0];
			$tmp_hauteur=$tmp_size[1];
		}
	}
	return "<div style='float:right; width:18px;'><a href=\"javascript:insere_image_dans_ckeditor('".$url_img."','$tmp_largeur','$tmp_hauteur')\" title='Insérer cette image dans le texte'><img src='../images/up.png' width='18' height='18' alt='Insérer cette image dans le texte' /></a></div>";
}
?>
