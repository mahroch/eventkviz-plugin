<?php
require_once( plugin_dir_path( __FILE__ ) . 'class-eventkviz-quiz.php' );

class Eventkviz_Statistika_Class extends Eventkviz_Quiz_Class{
    
    public function __construct() {


    }

    public static function load_shortcodes() {
        $plugin = new self();
        add_shortcode( 'statistika', array( $plugin, 'statistika' ) );
    }


    public function statistika($atts = '') {

        $value = shortcode_atts( array(
            'type' => '',
            'akcia' => ''
        ), $atts );

        global $wpdb;
       
         $this->load_basic_event_settings( $value['akcia']);

         $this->all_quizes_settings($value['akcia']);

        if ($this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === true){

            $results = $wpdb->get_results($wpdb->prepare("
                SELECT quiz_type, MAX(points) as points, user, team
                FROM pmgonijet_cct_results
                WHERE akcia = %s
                GROUP BY quiz_type, user
            ", $value['akcia']));

            $cumulative_points = array();
            $users = array();
            if(!empty($results)) {
                foreach ($results as $result) {
                    //$data = maybe_unserialize($result->akcia);
                    //$akcia = $data['akcia'];
                    $team = $result->team;
                    $points = $result->points;

                    // Compute cumulative points for team
                    if (!isset($cumulative_points[$team])) {
                        $cumulative_points[$team] = 0;
                    }
                    $cumulative_points[$team] += $points;

                    // Add user to team
                    if (!isset($users[$team])) {
                        $users[$team] = array();
                    }
                    $users[$team][] = array('user' => $result->user, 'points' => $points);
                }

                // Sort teams by cumulative points in descending order
                arsort($cumulative_points);

                // Sort users in each team by points in descending order
                foreach ($users as &$team_users) {
                    usort($team_users, function($a, $b) {
                        return $b['points'] - $a['points'];
                    });
                }

                // Display cumulative points for each team
                echo "<h2>Cumulative points of teams</h2>";
                echo "<ol>";
                foreach ($cumulative_points as $team => $points) {
                    echo "<li>{$team}: {$points} points</li>";
                }
                echo "</ol>";
             }
            // Display users and their points for each team

            echo "<h2>Teams grouped by quiz</h2>";
            //$sql = "SELECT quiz_type, MAX(points) as cumulative_points, team FROM pmgonijet_cct_results WHERE akcia = '" . $value['akcia'] . "' GROUP BY quiz_type, user";
        $sql = $wpdb->prepare("SELECT quiz_type, user, MAX(points) AS max_points
        FROM pmgonijet_cct_results
        WHERE akcia = %s
        GROUP BY quiz_type, user", $value['akcia']);

$results = $wpdb->get_results($sql);

// Initialize an empty array to store the cumulative points for each user group by quiz_type
$cumulative_points = array();

// Loop through the results and calculate the cumulative points for each user group by quiz_type
foreach ($results as $result) {
    $quiz_type = $result->quiz_type;
    $points = $result->max_points;
    $user = $result->user;

    if (!isset($cumulative_points[$quiz_type])) {
        $cumulative_points[$quiz_type] = array();
    }

    if (!isset($cumulative_points[$quiz_type][$user])) {
        $cumulative_points[$quiz_type][$user] = $points;
    } else {
        $cumulative_points[$quiz_type][$user] += $points;
    }
}

// Print the cumulative points for each user group by quiz_type
foreach ($cumulative_points as $quiz_type => $users) {
    echo "<b>Quiz Type: $quiz_type</b><br>";
    foreach ($users as $user => $points) {
        echo "User: $user, Cumulative Points: $points<br>";
    }
}

                echo "<h2>Unique users in quiz type</h2>";
                $results = $wpdb->get_results($wpdb->prepare("
                    SELECT r.quiz_type, r.user, SUM(r.points) as total_points
                    FROM (
                        SELECT quiz_type, user, MAX(points) as points
                        FROM pmgonijet_cct_results
                        WHERE akcia = %s
                        GROUP BY quiz_type, user
                    ) as m
                    INNER JOIN pmgonijet_cct_results as r
                    ON m.quiz_type = r.quiz_type
                    AND m.user = r.user
                    AND m.points = r.points
                    GROUP BY r.quiz_type, r.user
                ", $value['akcia']));

                foreach ($results as $result) {
                    echo $result->user . ' (' . $result->quiz_type . '): ' . $result->total_points . ' points<br>';
                }

                echo "<h2>Unique users for each team</h2>";

                $results = $wpdb->get_results($wpdb->prepare("
                SELECT team, COUNT(DISTINCT user) as unique_users
                FROM pmgonijet_cct_results
                WHERE akcia = %s
                GROUP BY team
                ORDER BY unique_users DESC
                ", $value['akcia']));

                foreach ($results as $result) {
                    echo $result->team . ': ' . $result->unique_users . ' unique users<br>';
                }



                echo "<h2>Users with total points</h2>";
                    $results = $wpdb->get_results($wpdb->prepare("
                    SELECT quiz_type, MAX(points) as cumulative_points, user
                    FROM pmgonijet_cct_results
                    WHERE akcia = %s
                    GROUP BY quiz_type, user
                ", $value['akcia']));

                // Initialize an empty array to store the cumulative points for each user
                $cumulative_points = array();

                // Loop through the results and calculate the cumulative points for each user
                foreach ($results as $result) {
                    $quiz_type = $result->quiz_type;
                    $points = $result->cumulative_points;
                    $user = $result->user;

                    if (!isset($cumulative_points[$user])) {
                        $cumulative_points[$user] = 0;
                    }

                    $cumulative_points[$user] += $points;
                }
                arsort($cumulative_points);
                // Print the list of users with their cumulative points
                echo '<ol>'; 
                foreach ($cumulative_points as $user => $points) {
                    echo "<li>User: $user - $points points<br>";
                }
                echo '</ol>'; 

        } elseif ($this->cAkcia->all_quizes_settings['identifikacia_userov_timu'] == true && $this->cAkcia->all_quizes_settings['identifikacia_kodom_usera'] === false){
            
            //echo 'toto';
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT quiz_type, MAX(points) as points, team
                FROM pmgonijet_cct_results
                WHERE akcia = %s
                GROUP BY quiz_type, team
            ", $value['akcia']));

            $cumulative_points = array();
            $users = array();
            foreach ($results as $result) {
                //$data = maybe_unserialize($result->akcia);
                //$akcia = $data['akcia'];
                $team = $result->team;
                $points = $result->points;

                // Compute cumulative points for team
                if (!isset($cumulative_points[$team])) {
                    $cumulative_points[$team] = 0;
                }
                $cumulative_points[$team] += $points;
            }

            // Sort teams by cumulative points in descending order
            arsort($cumulative_points);

            // Display cumulative points for each team
            echo "<h2>Cumulative points of teams</h2>";
            echo "<ol>";
            foreach ($cumulative_points as $team => $points) {
                echo "<li>{$team}: {$points} points</li>";
            }
            echo "</ol>";

            // Display users and their points for each team

            echo "<h2>Teams grouped by quiz</h2>";
                $results = $wpdb->get_results($wpdb->prepare("
                    SELECT quiz_type, MAX(points) as cumulative_points, team
                    FROM pmgonijet_cct_results
                    WHERE akcia = %s
                    GROUP BY quiz_type, team
                ", $value['akcia']));

                // Initialize an empty array to store the cumulative points for each user group by quiz_type
                $cumulative_points = array();

                // Loop through the results and calculate the cumulative points for each user group by quiz_type
                foreach ($results as $result) {
                    $quiz_type = $result->quiz_type;
                    $points = $result->cumulative_points;
                    $team = $result->team;

                    if (!isset($cumulative_points[$quiz_type])) {
                        $cumulative_points[$quiz_type] = array();
                    }

                    if (!isset($cumulative_points[$quiz_type][$team])) {
                        $cumulative_points[$quiz_type][$team] = $points;
                    } else {
                        $cumulative_points[$quiz_type][$team] += $points;
                    }
                }

                // Print the cumulative points for each user group by quiz_type
                foreach ($cumulative_points as $quiz_type => $teams) {
                    echo "<b>Quiz Type: $quiz_type</b><br>";
                    foreach ($teams as $team => $points) {
                        echo "Team: $team, Cumulative Points: $points<br>";
                    }
                }
            
            
        } else {
            
        }
    }
}