<?php
//helper file 
//basic_helper

//return number of working days in a month
//format echo countDays(2013, 1, 1, array(0, 6)); 0 -sunday and 6 saturday
function countDays($year, $month, $day, $ignore) {
    $count = 0;
    $counter = mktime(0, 0, 0, $month, $day, $year);
    while (date("n", $counter) == $month) {
        if (in_array(date("w", $counter), $ignore) == false) {
            $count++;
        }
        $counter = strtotime("+1 day", $counter);
    }
    return $count;
}

?>