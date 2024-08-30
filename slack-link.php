<?php
require_once '.functions.php';

$conn = openDatabaseConnection();
if (is_null($conn)) {
    echo "Database connection failed to initialize!";
    return;
}

include '.db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $slacktoken) {
        die("Nope.");
    }
    if (isset($_POST["slack_id"]) && isset($_POST['member_id'])) {
        $statement = $conn->prepare("SELECT * FROM members WHERE id = :memID");
        $statement->bindParam(":memID", $_POST['member_id']);
        $statement->execute();
        $user = $statement->fetch();
        // If the given member ID isn't in our database we return a message stating that
        if (!$user) {
            echo "Invalid user id! Please enter another one";
            return;
        }
        $statement = $conn->prepare("UPDATE members SET slackID = :slack WHERE id = :memID");
        $statement->execute(['slack' => $_POST["slack_id"], 'memID' => $_POST['member_id']]);
        echo "Successfully linked " . $_POST['slack_id'] . " to " . $user['first_name'] . " " . $user['last_name'] . " (" . $_POST['member_id'] . ")";
    } else {
        echo "Invalid request";
        return;
    }
} else {
    if (!isset($_GET['token']) || $_GET['token'] != $slacktoken) {
        die("Nope.");
    }
    if (isset($_GET['slack_id']) && isset($_GET['type'])) {
        if ($_GET['type'] != "info") {
            return;
        }
        $statement = $conn->prepare("SELECT * FROM members WHERE slackID = :slack");
        $statement->bindParam(":slack", $_GET['slack_id']);
        $statement->execute();
        $accounts = $statement->fetchAll();
        if (!$accounts) {
            echo "No website accounts are associated with this ID!";
            return;
        } else {
            foreach ($accounts as $account) {
                $message = "";
                // I need to move this to a separate function
                if ($account['captain'] == 1) {
                    $message .= "*Captain*\n";
                } elseif ($account['firstlt'] == 1) {
                    $message .= "*First Lieutenant*\n";
                } elseif ($account['secondlt'] == 1) {
                    $message .= "*Second Lieutenant*\n";
                } elseif ($account['pres'] == 1) {
                    $message .= "*President*\n";
                } elseif ($account['vicepres'] == 1) {
                    $message .= "*Vice President*\n";
                } elseif ($account['schedco'] == 1) {
                    $message .= "*Scheduling Coordinator*\n";
                } elseif ($account['traincommchair'] == 1) {
                    $message .= "*Training Committee Chair*\n";
                } elseif ($account['radioco'] == 1) {
                    $message .= "*Radio Coordnator*\n";
                } elseif ($account['cprco'] == 1) {
                    $message .= "*CPR Coordinator*\n";
                } elseif ($account['qaco'] == 1) {
                    $message .= "*QA/QI Coordinator*\n";
                }
                $message .= "Name: " . $account['first_name'] . " " . $account['last_name'];
                // They have a radio number
                if ($account['radionum'] != 0) {
                    $message .= " (" . $account['radionum'] . ")";
                }
                $message .= "\n";
                if (isset($_GET['admin']) && $_GET['admin'] == 1) {
                    $phone_num = $account['cell_phone'];
                    // Removes everything except the number from the phone numbers
                    $phone_num = preg_replace('/[^0-9.]+/', '', $phone_num);
                    $phone_num = substr_replace($phone_num, '-', 3, 0);
                    $phone_num = substr_replace($phone_num, '-', 7, 0);
                    $message .= "Phone: " . $phone_num;
                    $message .= "\n";
                }
                $message .= "Email: " . $account['email'];
                $message .= "\n";
                $message .= "Credentials:";
                $attendant = $account['attendant'] == 1;
                $message_length = $message.length;
                // This also needs to go into another functions
                if ($account['dutysup'] == 1) {
                    $message .= " Duty Supervisor";
                    echo $message;
                    return;
                } else {
                    if ($account['ees'] == 1) {
                        $message .= " EES,";
                    }
                    if ($account['cctrainer'] == 1) {
                        $message .= " CC-T,";
                    } elseif ($account['crewchief'] == 1) {
                        $message .= " CC,";
                    } elseif ($account['backupcc'] == 1) {
                        $message .= " P-CC,";
                    } elseif ($account['clearedcc'] == 1) {
                        $message .= " A-CC";
                    }
                    if ($account['firstresponsecc'] == 1) {
                        $message .= " FR-CC,";
                    }
                    if ($account['drivertrainer'] == 1) {
                        $message .= " D-T,";
                    } elseif ($account['driver'] == 1) {
                        $message .= " D,";
                    } elseif ($account['backupdriver'] == 1) {
                        $message .= " P-D";
                    } elseif ($account['cleareddriver'] == 1) {
                        $message .= " A-D";
                    }
                }
                if ($attendant && $message.length == $message_length) {
                    $message .= " A";
                } elseif ($message.length == $message_length) {
                    $message .= " O";
                } else {
                    $message = rtrim($message, ',');
                }
                $message .= "\n";
                echo $message;
                return;
            }
            return;
        }
    } elseif (isset($_GET['slack_id']) && !isset($_GET['type'])) {
        $statement = $conn->prepare("SELECT id, first_name, last_name FROM members WHERE slackID = :slack");
        $statement->bindParam(":slack", $_GET['slack_id']);
        $statement->execute();
        $accounts = $statement->fetchAll();
        if (!$accounts) {
            echo "No website accounts are associated with this ID!";
            return;
        } else {
            $message = $_GET['slack_id'] . " is linked with";
            foreach ($accounts as $account) {
                $message .= ", " . $account['first_name'] . " " . $account['last_name'] . " (" . $account['id'] . ")";
            }
            echo $message;
            return;
        }
    } else {
        die("You've provided an invalid request.");
    }
}
