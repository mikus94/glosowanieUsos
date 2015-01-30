<!DOCTYPE HTML> 
<html>
<head>
<style>
</style>
</head>
<body> 

<?php
if ( !current_user_can( 'manage_options' ) )  {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$staff = test_input($_POST["staff"]);
$pytanie= test_input($_POST["pytanie"]);

function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}
?>

<h1>Głosowanie typu "Lubię to!"</h1>

<form method="post" >

   <br><br>
   Pytanie:<br> <input type="text" name="pytanie" size="80" value="">
   <br>Dla kogo ma być przeznaczone głosowanie?
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="0") echo "checked"; ?> value="2">Tylko studenci
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="1") echo "checked"; ?> value="1">Tylko nauczyciele
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="2") echo "checked"; ?> value="-1">Wszyscy
   <br><br>
   Czy głosowanie ma mieć termin ważności?
   Jeśli nie pozostaw puste
   <br>W przeciwnym wypadku wpisz termin ważności w formacie RRRR-MM-DD GG-MM-SS
   <br><input type="datetime-local" name="czas">
   <br><br>
   Czy chcesz aby możnabyło obejrzeć wyniki głosowania?
   <br><input type="radio" name="wyniki" value="1">Tak
   <br><input type="radio" name="wyniki" value="2">Nie
   <br><br>
   <input type="submit" name="submit" value="Stwórz głosowanie"> 
</form>

<?php

global $wpdb;
$current_user = wp_get_current_user();
$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'" ) );
$id_us = $ajdi[0];

if (!empty($pytanie) and isset($pytanie) and !empty($staff) and isset($staff) and isset($_POST['wyniki']) and !empty($_POST['wyniki'])){
	echo "<h2>Twoje pytanie było następujące:</h2>";
	echo $pytanie;
	echo "<br>";
	if ($staff == 1)
		echo "<br>Głosowanie tylko dla nauczycieli";
	if ($staff == 2)
		echo "<br>Głosowanie tylko dla studentów";
	if ($staff == -1)
		echo "<br>Głosowanie dla wszystkich";
	if (isset($_POST['czas']) and !empty($_POST['czas'])){
		$czass = $_POST['czas'];
		echo "<br>Głosowanie ważne do $czass";
	}
	if (intval($_POST['wyniki']) == 1){
		echo "<br>Wyniki bedize można podglądać w trakcie głosowania";
	}else{
		echo "<br>Wyniki będą dostępne tylko dla administratorów";
	}	
	$wpdb->insert(
		'Pytanie',
		array(
			pytanie => $pytanie,
			typ => 3,
			id_tworcy => $id_us
		),
		array(
			'%s',
			'%d',
			'%d'
		)
	);
	
	$wpdb->insert(
		'PytanieSuwakowe',
		array(
			id_pytania => $wpdb->get_var( $wpdb->prepare("SELECT id_pytania FROM Pytanie WHERE pytanie='%s'", $pytanie) ),
			n => 1,
		),
		array(
			'%d',
			'%d'
		)
	);
	$pytanie_id = $wpdb->get_var( $wpdb->prepare("SELECT id_pytania FROM Pytanie WHERE pytanie='%s' ORDER BY id_pytania DESC", $pytanie) );
	$wpdb->insert(
		'Warunek',
		array(
			id_pytania => $pytanie_id,
			warunek => $staff,
		),
		array(
			'%d',
			'%s'
		)
	);
	$data;
	if (isset($_POST['czas']) and !empty($_POST['czas'])){
		$data = $_POST['czas'];
	} else {
		$data = "2030-12-01 00:00:00";
	}
	$wynik = $_POST['wyniki'];
	$wpdb->insert(
		'Termin',
		array(
			id_pytania => $pytanie_id,
			termin => $data,
			aktywne => 1,
			wyniki => $wynik
		)
	);
	echo "<br><br><h3> Twoje głosowanie zostało dodane pomyślnie :)</h3>";
}

?>

</body>
</html>
