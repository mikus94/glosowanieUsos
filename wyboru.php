<script type="text/javascript" src="//code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($){
    $('.my-form .add-box').click(function(){
        var n = $('.text-box').length + 1;
        var box_html = $('<p class="text-box"><label for="box' + n + '">Odpowiedź <span class="box-number">' + n + '</span></label> <input type="text" name="boxes[]" value="" id="box' + n + '" /></p>');
        box_html.hide();
        $('.my-form p.text-box:last').after(box_html);
        box_html.fadeIn('slow');
        return false;
    });
});
</script>

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


$staff =  test_input($_POST["staff"]);
$wwyboru = test_input($_POST["wwyboru"]);
$odp1 = test_input($_POST["odp1"]);
$odp2= test_input($_POST["odp2"]);
$odp3= test_input($_POST["odp3"]);
$odp4= test_input($_POST["odp4"]);
$pytanie= test_input($_POST["pytanie"]);

function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}/*
$pytanie = "";
$wwyboru = "-1";
$odp1 = "";
$odp2 = "";*/
?>

<h2>Głosowanie wyboru</h2>

<form method="post" >

   <br><br>
   Pytanie:<br> <input type="text" name="pytanie" size="80" value="">

   <br><br>
	<div class="my-form">
        <p class="text-box">
            <label for="box1">Odpowiedź <span class="box-number">1</span></label>
            <input type="text" name="boxes[]" value="" id="box1" />
            <a class="add-box" href="#">Add More</a>
        </p>
	</div>
   
   
   
   <br><br>
   Wielokrotnego wyboru?:
   <input type="radio" name="wwyboru" <?php if (isset($wwybor) && $wwyboru=="1") echo "checked";?>  value="1">Nie
   <input type="radio" name="wwyboru" <?php if (isset($wwybor) && $wwyboru=="2") echo "checked";?>  value="2">Tak
   <br>Dla kogo ma być przeznaczone głosowanie?
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="0") echo "checked"; ?> value="2">Tylko studenci
   <br><input type="radio" name="staff" <?php if (isset($staff) && $staff=="1") echo "checked"; ?> value="1">Tylko Pracownicy
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

$i = 0;

foreach($_POST["boxes"] as $key)
	if (!($key == ""))
		$i = $i + 1;

if (!empty($pytanie) and isset($pytanie) and $i > 1 and isset($wwyboru) and !empty($wwyboru) and !empty($staff)  and isset($_POST['wyniki']) and !empty($_POST['wyniki'])){
	echo "<h2>Twoje pytanie było następujące:</h2>";
	echo $pytanie;
	echo "<br>";
	echo "<h2>Twoje odpowiedzi do tego pytanie:</h2>";

	foreach($_POST["boxes"] as $key){
		if(!($key == ""))
			echo $key."<br>";
	}
	if ( isset($wwyboru) and !empty($wwyboru) ){
		if ($wwyboru == 1){
			echo "Jednokrotnego wyboru<br>";
		} else {
			echo "Wielokrotnego wyboru<br>";
		}
	}
	if ($staff == 1){
		echo "Głosowanie tylko dla nauczycieli";
	} elseif ($staff == 2) {
		echo "Głosowanie tylko dla studentów";
	} elseif ($staff == -1) {
		echo "Głosowanie dla wszystkich";
	}
	if (isset($_POST['czas']) and !empty($_POST['czas'])){
		$czass = $_POST['czas'];
		echo "<br>Głosowanie ważne do $czass";
	}
}

$i = 0;

foreach($_POST["boxes"] as $key){
	if (!($key == "")){
		$i = $i + 1;
	} 
}
global $wpdb;
$current_user = wp_get_current_user();
$ajdi = $wpdb->get_col( $wpdb->prepare( "SELECT id_osoby FROM Osoba WHERE imie='$current_user->user_firstname' AND nazwisko='$current_user->user_lastname'" ) );
$id_us = $ajdi[0];
/*zmienic warunek*/
if (!empty($pytanie) and ($i > 1) && !empty($wwyboru) and !empty($staff)  and isset($_POST['wyniki']) and !empty($_POST['wyniki'])){

	$wpdb->insert(
		'Pytanie',
		array(
			pytanie => $pytanie,
			typ => $wwyboru,
			id_tworcy => $id_us
		),
		array(
			'%s',
			'%d',
			'%d'
		)
	);
	$pytanie_id = $wpdb->get_var( $wpdb->prepare("SELECT id_pytania FROM Pytanie WHERE pytanie='%s' ORDER BY id_pytania DESC", $pytanie) );
	foreach($_POST["boxes"] as $key){
		if (!($key == "")){
			$wpdb->insert(
				'Odpowiedzi',
				array(
					id_pytania => $pytanie_id,
					tresc => $key,
				),
				array(
					'%d',
					'%s'
				)
			);
		}

	}
	
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
	$wyniki = $_POST['wyniki'];
	$wpdb->insert(
		'Termin',
		array(
			id_pytania => $pytanie_id,
			termin => $data,
			aktywne => 1,
			wyniki => $wyniki
		)
	);
	echo "<h3> Twoje głosowanie dodane pomyślnie :)</h3>";



}

?>

</body>
</html>
