<?php

global $wpdb;
session_start();
##sprawdzam czy dopiero wybieram pytania czy juz wybralem badz oddalem glos
if ((!isset($_POST['submit']) and empty($_POST['submit'])) and !isset($_POST['dozrobienia']) and empty($_POST['dozrobienia']) ){	
	$pytania_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id_pytania FROM Pytanie"));
	?>
	<br><br>
	<h2>Wybierz głosowanie, które chcesz edytować:</h2>
	<br><br>

	<form method=post ><select name="pytanie">
	<?php	
	foreach($pytania_ids as $key){
		$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$key" ) );
		?>
		<option value="<?php echo $key ?>"><?php echo $tresc[0] ?></option>
		<?php
	}
	?>
	</select>
		
		<p>Co chcesz zrobić?</p>
		<input type="radio" name="rob" value="dodaj">Dodaj głosy
		<br><input type="radio" name="rob" value="usun">Usuń głosy
		<br><input type="radio" name="rob" value="calosc">Usuń całe głosowanie
		<br><input type="radio" name="rob" value="aktywacja">Aktywacja głosowanie
		<br><input type="radio" name="rob" value="dezaktywacja">Dezaktywuj głosowanie
		<br><p><input type="submit" name="submit" value="Wybierz"></p>
	</form>
	<?php
} else {
	##edycjaaaa

	if (isset($_POST['rob']) and !empty($_POST['rob']) and !isset($_POST['dozrobienia']) and empty($_POST['dozrobienia'])){
		##przechodze do edycji
		$_SESSION['dorob'] = $_POST['rob'];
		$_SESSION['pytanie'] = $_POST['pytanie'];
		
		$id_pyt = $_POST['pytanie'];
		$typ = $wpdb->get_col( $wpdb->prepare( "SELECT typ FROM Pytanie WHERE id_pytania=$id_pyt") );
		$_SESSION['typ'] = $typ[0];

		$pytar = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$id_pyt" ) );
		$pyt = $pytar[0];
		if ($_POST['rob'] == "usun" ) {
			## chce usunąć jakieś głosy
			echo "<h2>Wybierz głosy do usunięcia</h2>";
			echo "<br><big>$pyt</big></br>";
			## sprawdzam czy głos jest suwakowy czy wyboru aby odwołać się do dobrej tabeli
			$_SESSION['dorob'] = $_POST['rob'];
			$_SESSION['pytanie'] = $pyt;
			$_SESSION['typ'] = $typ[0];
			$odpowiedzi;
			if ( $typ[0] > 2 ){
				## pytanie suwakowe
				$odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT id_glosu FROM GlosSuwakowy WHERE id_pytania=$id_pyt" ) );

			} elseif ( ($typ[0] < 3) and ($typ[0] > 0) ){
				$odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT id_glosu FROM GlosyWyboru WHERE id_pytania=$id_pyt" ) );
			}

			if (!empty($odpowiedzi)){
				?>
					<form method=post>
				<?php
				foreach($odpowiedzi as $id_o){
					$tresc;
					if ( $typ[0] > 2  ) {
						$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT wartosc FROM GlosSuwakowy WHERE id_glosu=$id_o") );
					} else {
						$id_odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT id_odpowiedzi FROM GlosyWyboru WHERE id_glosu=$id_o" ) );
						$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT tresc FROM Odpowiedzi WHERE id_odpowiedzi=$id_odpowiedzi[0]") );
					}

					?>
					
						<input type="checkbox" name="dousuniecia[]" value=<?php echo $id_o; ?>><?php echo $tresc[0]; ?>
						<br>
					<?php
				}
				?>
						<input type="submit" name="dozrobienia" value="Wykonaj">
					</form>
				
				<?php		
			} else {
				echo "<br><big>Niestety to pytanie nie posiada jeszcze odopwiedzi, zatem nie ma czego usuwać :)</big><br>";
			}
			

		} elseif ( $_POST['rob'] == 'calosc'  ) {
			echo "<h1>Czy jesteś pewien że chcesz usunąć całe głosowanie?!</h1>";
			echo "<br><big>Głosowanie: ".$pyt;
			?>	<form method=post>
				<br><br><input type="submit" name="dozrobienia" value="Tak jestem pewien.">
				</form>
			<?php

		} elseif ( $_POST['rob'] == 'dodaj'  ) {
			## dodaj głosy
			echo "<h1>Wybierz jaką odpowiedź chcesz dodać, a następnie w okienku ile razy:</h1>";
			echo "<h2>Pytanie: $pyt </h2>";
			if ( ($typ[0] < 3) and ($typ[0] > 0)  ) {
				## pytania wyboru
				
				$odpowiedzi = $wpdb->get_col( $wpdb->prepare( "SELECT id_odpowiedzi FROM Odpowiedzi WHERE id_pytania=$id_pyt" ) );
				?>
					<form method=post>
						<select name="odpowiedzi">
				<?php

				foreach($odpowiedzi as $id_o){
					$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT tresc FROM Odpowiedzi WHERE id_odpowiedzi=$id_o") );
					echo "tt".$tresc[0];
					print_r($tresc);
					?>
						<option value="<?php echo $id_o; ?>"><?php echo $tresc[0]; ?></option>

					<?php				
				}
				?>
						</select>
						<br><br><input type="text" name="ilerazy" value="">
						<br><br><input type="submit" name="dozrobienia" value="gotowe">
					</form>
				<?php
			} elseif ( $typ[0] == 3 ) {
				## lubie to
				## dodaj glos
				## lubie to = 1 --- nie lubie 0			
				?>
				<form method=post>
					<br><br>
					<input type="radio" name="odpowiedzi" value="1">Lubię to!
					<br><input type="radio" name="odpowiedzi" value="0">Nie lubię tego!
					<br><br>
					<input type="text" name="ilerazy" value="">
					<br><br><input type="submit" name="dozrobienia" value="Dodaj">
	
				<?php
			} elseif ($typ[0] == 4 ) {
					##gwiazdki
					?>
						<form method=post>
							<input type="radio" name='odpowiedzi' value=1>
							<input type="radio" name='odpowiedzi' value=2>
							<input type="radio" name='odpowiedzi' value=3>
							<input type="radio" name='odpowiedzi' value=4>
							<input type="radio" name='odpowiedzi' value=5>
							<br>&#9734 &#9734 &#9734 &#9734 &#9734</br>
							<br><br>
							<br><br>
							<input type="text" name="ilerazy" value="">
							<input type="submit" name='dozrobienia' value='Dodaj'></br>
						</form>
					<?php
			} elseif ( $typ[0] == 5 ) {
					## mam suwak
					$dl_suwaka = $wpdb->get_col ( $wpdb->prepare ( "SELECT n FROM PytanieSuwakowe WHERE id_pytania=$id_pyt" ) );
					
					?>
						<form method=post>
							<input type="range" name='odpowiedzi' min="0" max="<?php echo $dl_suwaka[0]; ?>" value="0" step="1" onchange="showValue(this.value)" size=100 /></br>
							<span id="range">0</span>
							<br><br>
							<input type="text" name="ilerazy" value="">
							<br><br><input type="submit" name="dozrobienia" value="Dodaj">
						</form>
						<script type="text/javascript">
						function showValue(newValue)
						{
							document.getElementById("range").innerHTML=newValue;
						}
						</script>
					<?php
			}
		} elseif ($_POST['rob'] == 'aktywacja') {
			## aktywacja głosowania
			$id_pyt = $_POST['pytanie'];
			$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$id_pyt" ) );
			echo "<h1>Głosowanie &quot $tresc[0] &quot zostało aktywowane</h1>";
			$wpdb->update(
				'Termin',
				array(
					aktywne => 1
				),
				array(
					id_pytania => $id_pyt
				),
				array(
					'%d'
				),
				array(
					'%d'
				)
			);

		} elseif ($_POST['rob'] == 'dezaktywacja') {
			## deaktywacja glosowania
			$id_pyt = $_POST['pytanie'];
			$tresc = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytanie WHERE id_pytania=$id_pyt") );
			echo "<h1>Głosowanie &quot $tresc[0] &quot zostało dezaktywowane</h1>";
			$wpdb->update(
				'Termin',
				array(
					aktywne => 0
				),
				array(
					id_pytania => $id_pyt
				),
				array(
					'%d'
				),
				array(
					'%d'
				)
			);
		}




	}  else {
		## doZrobienia
		$id_pyt = $_SESSION['pytanie'];
		$trescar = $wpdb->get_col( $wpdb->prepare( "SELECT pytanie FROM Pytania WHERE id_pytania=$id_pyt") );
		$tresc = $trescar[0];
		if ($_SESSION['dorob'] == 'usun'){
			echo "<h1>Właśnie usunąłeś odpowiedzi do pytania:</h1>";
			echo "<h2>$tresc</h2>";
			if ( $_SESSION['typ'] > 2 ) {
				## suwakowy
				foreach($_POST['dousuniecia'] as $id_o){
					$wpdb->delete('GlosSuwakowy', array( 'id_glosu' => $id_o ), array( '%d' ) );	
				}
			} elseif ( ($_SESSION['typ'] < 3) and ($_SESSION['typ'] > 0) ) {
				## wyboru
				foreach ($_POST['dousuniecia'] as $id_o){
					$wpdb->delete('GlosyWyboru', array( 'id_glosu' => $id_o ), array( '%d' ) );
				}
			}
		} elseif ( $_SESSION['dorob'] == 'calosc'  ) {
			echo "<h1>Właśnie usunąłeś całe głosowanie:</h1>";
			echo "<h2>$tresc</h2>";
			if ( $_SESSION['typ'] > 2 ){
				## suwakowy
				$wpdb->delete('GlosSuwakowy', array( 'id_pytania' => $id_pyt ), array( '%d' ) );
				$wpdb->delete('PytanieSuwakowe', array( 'id_pytania' => $id_pyt ), array( '%d' ) );
				$wpdb->delete('Pytanie', array( 'id_pytania' => $id_pyt ), array( '%d' ) );

			} elseif ( ($_SESSION['typ'] < 3) and ($_SESSION['typ'] > 0) ) {
				$wpdb->delete('GlosyWyboru', array( 'id_pytania' => $id_pyt ), array( '%d' ) );
				$wpdb->delete('Odpowiedzi', array( 'id_pytania' => $id_pyt ), array( '%d' ) );
				$wpdb->delete('Pytanie', array( 'id_pytania' => $id_pyt ), array( '%d' ) );
			}
		} elseif ( $_SESSION['dorob'] == 'dodaj' ) {
			## dodaje odpowiedzi
			## dodaje głosy jako admin żeby nie zablokowac nikomu odpowiadania na pytanie :)
			$id_os = 0;
			echo "<h1>Właśnie dodałeś do pytania:</h1>";
			echo "<h2>$tresc</h2>";
			$id_o = $_POST['odpowiedzi'];
			$tresc_odp;
			if ( ($_SESSION['typ'] < 3) and ($_SESSION['typ'] > 0) ){
				$tresc_odp = $wpdb->get_col( $wpdb->prepare( "SELECT tresc FROM Odpowiedzi WHERE id_odpowiedzi=$id_o" ) );
				echo "<br><h3>$tresc_odp[0],  ";
			} elseif ($_SESSION['typ'] > 3 ) {
				echo "<br><h3> $id_o ";
			} elseif ($_SESSION['typ'] == 3){
				if ( $id_o == 1){
					echo "Lubię to! ";
				} else {
					echo "Nie lubię tego!";
				}
			}
			echo "".$_POST['ilerazy']." razy.</h3><br>";
			for($i = 1; $i <= $_POST['ilerazy']; $i++){
				if ( ($_SESSION['typ'] < 3) and ($_SESSION['typ'] > 0) ){
					$wpdb->insert(
						'GlosyWyboru',
						array(
							id_osoby => $id_os,
							id_pytania => $id_pyt,
							id_odpowiedzi => $_POST['odpowiedzi']
						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
				} elseif ( $_SESSION['typ'] > 2	) {
					$wpdb->insert(
						'GlosSuwakowy',
						array(
							id_osoby => $id_os,
							id_pytania => $id_pyt,
							wartosc => $_POST['odpowiedzi']

						),
						array(
							'%d',
							'%d',
							'%d'
						)
					);
				}

			}
			
		}
	}
}


?>
