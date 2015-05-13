<?php
require '../src/SfApi.php';

$sf = new Sf([
	'apiUser' => 'SFbioAPI',
	'apiPassword' => 'bSF5PFHcR4Z3'
]);

$availbleMovies = $sf->getAvailableMovies('BS');
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sf Bio Api</title>
    <link rel="stylesheet" type="text/css" href="assets/style.css">
  </head>
  <body>
    <div class="container">
      <header class="clearfix">
        <h1>Sf Bio <span>Display available movies</span></h1>
      </header>
      <div class="main">
        <ul class="grid">
          
          	<?php foreach($availbleMovies->movies as $movie): ?>
      		<li>
      			<div class="poster">
	      			<img src="<?php echo str_replace("_WIDTH_", "150", $movie->placeHolderPosterURL); ?>" />
      			</div>
          		<?php echo $movie->movieName; ?>
      		</li>
          	<?php endforeach; ?>
          
        </ul>
      </div>
    </div>
  </body>
</html>