<?php
    $error = null;
    $submitted = false;
    $formError = false;
    $validData = [];
    $matieres = [];


    try { 
        // connexion à la base de données
        $dbh = new PDO('mysql:host=localhost;port=3308;dbname=form_etudiant', 'root', '');
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // récupérer toutes les matieres
        $sqlSelectMatieres = 'SELECT id_matiere, libelle, coefficient FROM matiere;';
        $matieres = $dbh->query($sqlSelectMatieres)->fetchAll();

        // initialisation et pré-remplissage de la variable $validData
        $validData = ['nom'=>'', 'prenom'=>''];
        foreach ($matieres as $matiere) {
            $validData['notes'][$matiere['id_matiere']] = null;
        }

        if (is_array($_POST) && count($_POST) >= 1) {

            // vérification des notes
            if (!is_array($_POST['notes']) || empty($_POST['notes'])) {
                $formError = true;
            } else {
                foreach ($_POST['notes'] as $idMatiere => $note) {
                    if (!is_numeric($note) || (intval($note) < 0) || (intval($note) > 20)) {
                        $formError = true;
                        $validData['notes'][$idMatiere] = null;
                    } else {
                        $validData['notes'][$idMatiere] = intval($note);
                    }
                }
            }

            // validData va contenir les données qui sont valides.
            // validData permet de garder les données valides du fomulaire
            // et permet de les ré-injecter pour éviter que l'utilisateur
            // doivent retaper les bonnes valeurs

            // vérification du nom
            if (empty($_POST['nom'])) {
                $formError = true;
                $validData['nom'] = null;
            } else {
                $validData['nom'] = $_POST['nom'];
            }

            // vérification du prénom
            if (empty($_POST['prenom'])) {
                $formError = true;
                $validData['prenom'] = null;
            } else {
                $validData['prenom'] = $_POST['prenom'];
            }

            if (!$formError) {


                // on vérifie si l'étudiant n'existe pas déjà
                $stmt = $dbh->prepare("SELECT id_etudiant FROM etudiant WHERE nom=? AND prenom=?");
                $stmt->execute([$_POST['nom'], $_POST['prenom']]);
                $studentsIds = $stmt->fetchAll();

                // si l'étudiant existe,
                if (count($studentsIds) > 0) {
                    // on utilise son ID
                    $studentId = $studentsIds[0]["id_etudiant"];

                    // et on met à jour ses notes
                    foreach ($_POST['notes'] as $idMatiere => $note) {
                        $stmt = $dbh->prepare("UPDATE note SET id_etudiant=?, id_matiere=?, note=? WHERE id_etudiant=? AND id_matiere=?");
                        $stmt->execute([$studentId, $idMatiere, $note, $studentId, $idMatiere]);
                    }

                } else {

                    // sinon, on enregistre l'édudiant
                    $stmt = $dbh->prepare("INSERT INTO etudiant(nom, prenom) VALUES (:nom, :prenom)");
                    $stmt->bindParam(':nom', $_POST['nom']);
                    $stmt->bindParam(':prenom', $_POST['prenom']);
                    $stmt->execute();
                    $studentId = $dbh->lastInsertId();

                    // et on enregistre ses notes
                    foreach ($_POST['notes'] as $idMatiere => $note) {
                        $stmt = $dbh->prepare("INSERT INTO note(id_etudiant, id_matiere, note) VALUES (:id_etudiant, :id_matiere, :note)");
                        $stmt->bindParam(':id_etudiant', $studentId);
                        $stmt->bindParam(':id_matiere',  $idMatiere);
                        $stmt->bindParam(':note',        $note);
                        $stmt->execute();
                    }
                }


                $submitted = true;
            }
        }
    } catch (Exception $e) {
        error_log('Error. exeption: ' . $e->getMessage());

        $error = 'Une erreur technique est survenue.';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Formulaire étudiant</title>
</head>
<body>
    <div class="container padding-20">
        <!-- header -->
        <header>
            <div class="header-titre padding-10">
                <h1 class="titre">Saisie des notes</h1>
            </div>
        </header>

        <!-- <h3>$validData</h3>
        <pre><?php var_export($validData); ?></pre>
        <h3>$_POST</h3>
        <pre><?php var_dump($_POST); ?></pre> -->

        <?php if ($error !== null) : ?>

            <div class="error">
                <p><?php echo $error; ?></p>
            </div>

        <?php elseif ($submitted === true && $formError === false) : ?>

            <div class="submitted">
                <p>Merci ! Les notes ont bien été enregistrées.</p>
                <p> 
                    <a href="/formulaire.php"><strong>Saisir les notes d'un nouvel étudiant</strong></a>
                    <hr>
                    <a href="/moyenne.php"><strong>Consulter les moyennes</strong></a>
                </p>
            </div>

        <?php else : ?>

            <?php if ($formError === true) : ?>

                <div class="form-error">
                    <p>Une erreur est survenue dans le traitement des données du formulaire.</p>
                </div>

            <?php endif; ?>

            <div class="formulaire">
                <form method="POST" action="formulaire.php">
                    <table>
                        <tr>
                            <td class="form-titre">
                                <strong>ELEVE :</strong>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>
                                <label for="nom">Nom</label>
                            </td>
                            <td>
                                <input  class="form-champs" type="text" id="nom" name="nom" value="<?php echo $validData['nom'] ?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="prenom">Prénom</label>
                            </td>
                            <td>
                                <input class="form-champs" type="text" id="prenom" name="prenom" value="<?php echo $validData['prenom'] ?>"/>
                            </td>

                        </tr>
                        <tr>
                            <td class="form-titre">
                                <strong>NOTES :</strong>
                            </td>
                            <td></td>
                        </tr>
                        <?php
                            foreach ($matieres as $matiere) {
                                $idMatiere = intval($matiere['id_matiere']);
                        ?>
                        <tr>
                            <td>
                                <?php echo $matiere['libelle']; ?> (coeff. <?php echo $matiere['coefficient']; ?>)
                            </td>
                            <td>
                                <input class="form-champs"
                                    type="number"
                                    name="notes[<?php echo $idMatiere; ?>]"
                                    value="<?php echo $validData['notes'][$idMatiere] ?>"
                                />
                            </td>
                        </tr>
                        <?php } ?>

                        <tr></tr>
                        <tr>
                            <td></td>
                            <td align="center">
                                <br><input type="submit" value="Enregistrer" id ="btn-calcul" name="btn-calcul">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        <?php endif; ?>

        <!-- footer -->
        <footer>
            <p>©myGes</p>
        </footer>

    </div>
</body>
</html>