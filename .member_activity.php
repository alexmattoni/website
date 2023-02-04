  <?php
//header("Access-Control-Allow-Origin: *");
//header("Content-Type: application/json; charset=UTF-8");


require_once ".db_config.php";

include ".functions.php";
$connection = new PDO("mysql:host=$dhost;dbname=$dname", $duser, $dpassword);
$user = getUser($_GET['session_id'], $connection);
$username = $user['username'];

if (checkIfAdmin($connection)){
  $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if(!isset($dname)) {
    $dname = 'ambulanc_web';
  }

  // Selecting Database
  //$db = mysql_select_db("$dname", $connection);
  $connection->exec("USE `$dname`");

  $mindate = "2022-01-01";
  $maxdate = "2023-01-01";

  $both_sql =  "SELECT members.id, members.first_name, members.last_name, 
                  count(full_games.memberid) AS games_count,
                  count(night_crews.memberid) AS nights_count
                FROM members 
                INNER JOIN (
                  SELECT games_crews.memberid, games.date, games_crews.gameid
                  FROM games_crews 
                  INNER JOIN games
                    ON games.id = games_crews.gameid) AS full_games
                  ON members.id = full_games.memberid
                FULL OUTER JOIN (
                    SELECT id AS crewid, crews.date, cc AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, driver AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, attendant AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, observer AS memberid FROM crews
                    ORDER BY crewid ) AS night_crews
                  ON members.id = night_crews.memberid
                WHERE full_games.date > :mindate AND full_games.date < :maxdate 
                  AND night_crews.date > :mindate AND night_crews.date < :maxdate
                  AND members.id > 0
                GROUP BY members.id";

  $game_sql =  "SELECT members.id, members.first_name, members.last_name, count(full_games.memberid) AS games_count
                FROM members 
                INNER JOIN (
                  SELECT games_crews.memberid, games.date, games_crews.gameid
                  FROM games_crews 
                  INNER JOIN games
                    ON games.id = games_crews.gameid) AS full_games
                  ON members.id = full_games.memberid
                WHERE full_games.date > :mindate AND full_games.date < :maxdate AND members.id > 0
                GROUP BY members.id";

  $night_sql = "SELECT members.id, members.first_name, members.last_name, count(night_crews.memberid) AS nights_count
                FROM members
                INNER JOIN (
                    SELECT id AS crewid, crews.date, cc AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, driver AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, attendant AS memberid FROM crews
                    UNION ALL
                    SELECT id AS crewid, crews.date, observer AS memberid FROM crews
                    ORDER BY crewid ) AS night_crews
                  ON members.id = night_crews.memberid
                WHERE night_crews.date > :mindate AND night_crews.date < :maxdate AND members.id > 0
                GROUP BY members.id";


  $game_statement=$connection->prepare($game_sql);
  $game_statement->bindParam(':mindate', $mindate);
  $game_statement->bindParam(':maxdate', $maxdate);

  $game_statement->execute();
  $game_results=$game_statement->fetchAll(PDO::FETCH_ASSOC);

  $night_statement=$connection->prepare($night_sql);
  $night_statement->bindParam(':mindate', $mindate);
  $night_statement->bindParam(':maxdate', $maxdate);

  $night_statement->execute();
  $night_results=$night_statement->fetchAll(PDO::FETCH_ASSOC);

  // $both_statement=$connection->prepare($both_sql);
  // $both_statement->bindParam(':mindate', $mindate);
  // $both_statement->bindParam(':maxdate', $maxdate);

  // $both_statement->execute();
  // $both_results=$both_statement->fetchAll(PDO::FETCH_ASSOC);

  $merged_results = array_merge($night_results, $game_results);
  $clean_results = [];
                  
  foreach($merged_results as $row) {
    $id = intval($row['id']);
    // if (array_key_exists($id, $clean_results)) {

    foreach($row as $key=>$value) {
      if ($key == 'id') continue;
      if (!array_key_exists($key, $clean_results[$id])) {
        $clean_results[$id][$key] = $value;
      }
    }
  }

  $json=json_encode($clean_results, JSON_NUMERIC_CHECK);
  // $json=json_encode($merged_results,JSON_NUMERIC_CHECK);

  echo($json);

} else {
  echo 'Nice Try.';
}

?>