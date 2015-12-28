<!DOCTYPE html>
<html>
    <head>
        <title>Error</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <?php session_start(); if ( isset( $_SESSION['msgExcep'] ) ) { echo $_SESSION['msgExcep']; } ?>
    </body>
</html>
