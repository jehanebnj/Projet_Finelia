<?php

function getMatieres($dbh)
{
    $sqlSelectMatieres = 'SELECT id_matiere, libelle, coefficient FROM matiere;';
    $results = $dbh->query($sqlSelectMatieres)->fetchAll();

    $matieres = [];

    foreach ($results as $result) {
        $matieres[$result['id_matiere']] = [
            'libelle' => $result['libelle'],
            'coefficient' => $result['coefficient']
        ];
    }

    return $matieres;
}

function getEtudiantNotes($dbh, $matieres, $idEtudiant)
{
    $stmt = $dbh->prepare("SELECT id_matiere, note FROM note WHERE id_etudiant=?");
    $stmt->execute([$idEtudiant]);
    $results = $stmt->fetchAll();

    $notes = [];

    foreach ($results as $result) {
        if (!array_key_exists($result['id_matiere'], $matieres)) {
            // cette note concerne une matiere qui n'existe plus
            continue;
        }

        $matiere = $matieres[$result['id_matiere']];

        $notes[] = [
            'note' => floatval($result['note']),
            'matiereLibelle' => $matiere['libelle'],
            'matiereCoefficient' => intval($matiere['coefficient'])
        ];
    }

    return $notes;
}

function calculMoyenneEtudiant($notes)
{
    $total = 0;
    $coefficients = 0;

    foreach ($notes as $note) {
        $total += ($note['note'] * $note['matiereCoefficient']);
        $coefficients += $note['matiereCoefficient'];
    }

    if ($coefficients <= 0) {
      return null;
    }

    $moyenne = round($total / $coefficients, 2);

    return $moyenne;
}

$error = null;
$matieres = [];
$etudiants = [];

try {
    // connexion à la base de données
    $dbh = new PDO('mysql:host=localhost;port=3308;dbname=form_etudiant', 'root', '');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // récupérer toutes les matieres
    $matieres = getMatieres($dbh);

    // récupérer tous les etudiants
    $sqlSelectMatieres = 'SELECT id_etudiant, nom, prenom FROM etudiant;';
    $etudiants = $dbh->query($sqlSelectMatieres)->fetchAll();
} catch (Exception $e) {
    error_log('Error. exeption: ' . $e->getMessage());

    $error = 'Une erreur technique est survenue.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="style2.css">
  <title>Formulaire étudiant</title>
</head>
<body>
<div class="container">
  <br>
  <br>
  <!-- header -->
  <header>
    <div class="header-titre">
      <br>
      <h1 class="titre">Moyennes des étudiants</h1>
      <h2 class="st"></h2>
      <br>
    </div>
  </header>

  <!-- main -->
  <div class="formulaire-moy">
    <table>
      <tr>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Notes</th>
        <th>Moyenne</th>
      </tr>
        <?php foreach ($etudiants as $etudiant) : ?>
            <?php $notes = getEtudiantNotes($dbh, $matieres, $etudiant['id_etudiant']); ?>
          <tr>
            <td>
                <?php echo $etudiant['nom']; ?>
            </td>
            <td>
                <?php echo $etudiant['prenom']; ?>
            </td>
            <td>
                <?php foreach ($notes as $note) : ?>
                  <p>
                      <?php echo $note['matiereLibelle']; ?> (coeff. <?php echo $note['matiereCoefficient']; ?>) :
                      <?php echo $note['note']; ?>
                  </p>
                <?php endforeach; ?>
            </td>
            <td>
                <?php echo calculMoyenneEtudiant($notes); ?>
            </td>
          </tr>
        <?php endforeach; ?>
    </table>
  </div>

  <!-- footer -->
  <footer>
    <p>©myGes</p>
  </footer>
</div>
</body>
</html>