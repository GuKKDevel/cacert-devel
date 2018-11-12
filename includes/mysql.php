<?php /*
    LibreSSL - CAcert web application
    Copyright (C) 2004-2018  CAcert Inc.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

	$_SESSION['mconn'] = mysqli_connect(MCONN_HOST, MCONN_USER, MCONN_PASS);
	if ($_SESSION['mconn'] != FALSE)
	{
		mysqli_select_db("database");
		$_SESSION['mconn'] = TRUE;
	}
	$_SESSION['_config']['normalhostname'] = NORMALHOSTNAME;
	$_SESSION['_config']['securehostname'] = SECUREHOSTNAME;
	$_SESSION['_config']['tverify'] = TVERIFY;

	function sendmail($to, $subject, $message, $from, $replyto = "", $toname = "", $fromname = "", $errorsto = "returns@cacert.org", $use_utf8 = true)
	{
		$lines = explode("\n", $message);
		$message = "";
		foreach($lines as $line)
		{
			$line = trim($line);
			if($line == ".")
				$message .= " .\n";
			else
				$message .= $line."\n";
		}

		if($fromname == "")
			$fromname = $from;

		$bits = explode(",", $from);
		$from = addslashes($bits['0']);
		$fromname = addslashes($fromname);

		$smtp = fsockopen("localhost", 25);
		if(!$smtp)
		{
			echo("Could not connect to mailserver at localhost:25\n");
			return;
		}
		$InputBuffer = fgets($smtp, 1024);
		fputs($smtp, "HELO www.cacert.org\r\n");
		$InputBuffer = fgets($smtp, 1024);
		fputs($smtp, "MAIL FROM:<returns@cacert.org>\r\n");
		$InputBuffer = fgets($smtp, 1024);
		$bits = explode(",", $to);
		foreach($bits as $user)
			fputs($smtp, "RCPT TO:<".trim($user).">\r\n");
		$InputBuffer = fgets($smtp, 1024);
		fputs($smtp, "DATA\r\n");
		$InputBuffer = fgets($smtp, 1024);
		fputs($smtp, "X-Mailer: CAcert.org Website\r\n");
		if (array_key_exists("REMOTE_ADDR", $_SERVER))
			fputs($smtp, "X-OriginatingIP: ".$_SERVER["REMOTE_ADDR"]."\r\n");
		fputs($smtp, "Sender: $errorsto\r\n");
		fputs($smtp, "Errors-To: $errorsto\r\n");
		if($replyto != "")
			fputs($smtp, "Reply-To: $replyto\r\n");
		else
			fputs($smtp, "Reply-To: $from\r\n");
		fputs($smtp, "From: $from\r\n");
		if ( PROD_STATE == "prod") {
			fputs( $smtp, "To: $to\r\n" );
		} else {
			fputs( $smtp, "To: " . TEST_EMAIL_TO . "\r\n");
		}
		if(preg_match("/[^a-zA-Z0-9 .-\[\]!_@]/",$subject))
		{
			fputs($smtp, "Subject: =?utf-8?B?".base64_encode(recode("html..utf-8", $subject))."?=\r\n");
		}
		else
		{
			fputs($smtp, "Subject: $subject\r\n");
		}
		fputs($smtp, "Mime-Version: 1.0\r\n");
		if($use_utf8)
		{
			fputs($smtp, "Content-Type: text/plain; charset=\"utf-8\"\r\n");
		}
		else
		{
			fputs($smtp, "Content-Type: text/plain; charset=\"iso-8859-1\"\r\n");
		}
		fputs($smtp, "Content-Transfer-Encoding: quoted-printable\r\n");
		fputs($smtp, "Content-Disposition: inline\r\n");

//		fputs($smtp, "Content-Transfer-Encoding: BASE64\r\n");
		fputs($smtp, "\r\n");
//		fputs($smtp, chunk_split(base64_encode(recode("html..utf-8", $message)))."\r\n.\r\n");
		$encoded_lines = explode( "\n", str_replace("\r", "", $message) );
		array_walk( $encoded_lines,
			function (&$a) {
				$a = quoted_printable_encode(recode("html..utf-8", $a));
			});
		$encoded_message = implode("\n", $encoded_lines);

		$encoded_message = str_replace("\r.", "\r=2E", $encoded_message);
		$encoded_message = str_replace("\n.", "\n=2E", $encoded_message);
		fputs($smtp, $encoded_message);
		fputs($smtp, "\r\n.\r\n");
		fputs($smtp, "QUIT\n");
		$InputBuffer = fgets($smtp, 1024);
		fclose($smtp);
	}

?>
