<?

class HabrPost
{
    public $text, $filename;

    function link_file()
    {
        return '<a href="' . $this->filename . '">' . $this->filename . '</a>';
    }

    function month_to_id($text)
    {
        $monthes = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
        $n = array_search($text, $monthes);
        if ($n !== FALSE) {
            $n++;
            if (strlen($n) < 2)
                $n = '0' . $n;
            return $n;
        } else {
            echo 'WTF?! Failed to get month! Value "' . $text . '" ' . $this->link_file() . '!<br>';
            return FALSE;
        }
    }

    function get_date()
    {
        preg_match('/<div class=\"published\">(.*?)<\/div>/si', $this->text, $match);
        $dt = $match[1];
        $dt = trim($dt);
        $dt = explode(' ', $dt);
        if (is_numeric($dt[0])) {
            $day = $dt[0];
            $month = $dt[1];
            if (sizeof($dt) === 5) {
                $year = $dt[2];
                $time = $dt[4];

            } else {
                $time = $dt[3];
                $year = date("Y");
            }
        } else {
            if ($dt[0] === 'сегодня') {
                $day = date('d');
                $month = date('m');
                $year = date('Y');
                $time = $dt[2];
            } else if ($dt[1] === 'вчера') {
                $day = date('d', strtotime("-1 days"));
                $month = date('m', strtotime("-1 days"));
                $year = date('Y', strtotime("-1 days"));
                $time = $dt[2];
            } else {
                echo 'WTF?! Failed to get date! ' . $this->link_file() . '!<br>';
                return false;
            }
        }
        $month = $this->month_to_id($month);
        if (strlen($day) < 2)
            $day = '0' . $day;
        $mysql_date = $year . '-' . $month . '-' . $day . ' ' . $time . ':00';
        return $mysql_date;
    }

    function get_id()
    {
        preg_match('/<meta property=\"al:android:url\" content=\"habrahabr:\/\/post\/(.*?)" \/>/si', $this->text, $match);
        if ($match[1])
            return $match[1];
        else {
            echo 'WTF?! Failed to get id! ' . $this->link_file() . '!<br>';
            return false;
        }
    }

    function get_rate()
    {

        preg_match('/<span class=\"voting-wjt__counter-score js-score\" title=\"(.*?)\">(.*?)<\/span>/si', $this->text, $match);
        if ($match[2] !== FALSE) {
            $rate = $match[2];
            if ($rate[0] === '0')
                return '0';
            else {
                $sign = $rate[0];
                $num = '';
                for ($i = 1; $i < strlen($rate); $i++) {
                    if (is_numeric($rate[$i]))
                        $num .= $rate[$i];
                }
                $rate = $num;
                if ($sign !== '+')
                    $rate = -(int)$rate;
                return $rate;
            }
        } else {
            echo 'WTF?! Failed to get rate! ' . $this->link_file() . '!<br>';
            return false;
        }
    }

    function get_views()
    {
        preg_match('/<div class=\"views-count_post\" title=\"Просмотры публикации\">(.*?)<\/div>/si', $this->text, $match);
        $text = $match[1];
        if ($text) {
            if (strpos($text, 'k') !== FALSE) {
                return ((int)$text) * 1000;
            } else
                return $text;
        } else {
            echo 'WTF?! Failed to get views! ' . $this->link_file() . '!<br>';
            return false;
        }
    }

    function get_comments()
    {
        preg_match('/id=\"comments_count\">(.*?)<\/span>/si', $this->text, $match);
        if ($match[1] !== FALSE)
            return $match[1];
        else {
            echo 'WTF?! Failed to get comments count! ' . $this->link_file() . '!<br>';
            return false;
        }
    }

    function get_info()
    {
        return ['id' => $this->get_id(), 'date' => $this->get_date(),
            'rate' => $this->get_rate(), 'comments' => $this->get_comments(), 'views' => $this->get_views(),];
    }
}

$mysqli = new mysqli('localhost', 'habr', 'habr', 'habr');
$post = new HabrPost();
$errors = 0;
$success = 0;
for ($i = 0; $errors < 10; $i++) {
    if ($i == 0)
        $file = 'topics/index.html';
    else
        $file = 'topics/index.html.' . $i;
    $text = file_get_contents($file);
    if ($text) {
        $post->filename = $file;
        $post->text = $text;
        $info = $post->get_info();
        //print_R($info);
        if ($info['date'] === FALSE || $info['id'] === FALSE || $info['views'] === FALSE)
            continue;

        $success++;
        $sql = 'INSERT INTO posts(id,created,rate,comments,views) VALUES(?,?,?,?,?)';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('isiii', $info['id'], $info['date'],
            $info['rate'], $info['comments'], $info['views']);
        $res = $stmt->execute();

    } else $errors++;
}
echo '<p>Processed ' . ($i - $errors) . ' files, ' . $success . ' successfully</p>';
?>