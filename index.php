<?php 

// Authentication for Marvel API
$ts = date('Y-m-d-H:i:s');
$apikey = "";
$pkey = "";
$hash = md5($ts.$pkey.$apikey);

// If form is submitted through the Search button
if (isset($_POST['submit'])) {

// Replace the space in the URL with necessary characters
$character = preg_replace('/\s+/', '%20', $_POST['character']);

// Form the link of which qill be queried in the API
$link = "https://gateway.marvel.com/v1/public/characters?name=".$character."&limit=40&ts=".$ts."&apikey=".$apikey."&hash=".$hash;

// Get the contents of the webpage through JSON
$json = file_get_contents($link);

// Decode the data
$json_data = json_decode($json, true);

// Find the ID of the searched character. This is used within the type query
$char_id = $json_data['data']['results'][0]['id'];

  // Type must be specified for search or export
  if (isset($_POST['type'])) {
    // Form the specific type based on the character ID
    $link = "https://gateway.marvel.com/v1/public/".$_POST['type']."?characters=".$char_id."&limit=40&ts=".$ts."&apikey=".$apikey."&hash=".$hash;
    $json = file_get_contents($link);
    $json_data = json_decode($json, true);
    // Format type
    $type = ucfirst($_POST['type']);
  }
}
if (isset($_POST['export'])) {
  // Prepare character ID again for export
  $character = preg_replace('/\s+/', '%20', $_POST['character']);
  $link = "https://gateway.marvel.com/v1/public/characters?name=".$character."&limit=40&ts=".$ts."&apikey=".$apikey."&hash=".$hash;
  $json = file_get_contents($link);
  $json_data = json_decode($json, true);
  $char_id = $json_data['data']['results'][0]['id'];

    // Prepare type again for export
    if (isset($_POST['type'])) {
      $link = "https://gateway.marvel.com/v1/public/".$_POST['type']."?characters=".$char_id."&limit=40&ts=".$ts."&apikey=".$apikey."&hash=".$hash;
      $json = file_get_contents($link);
      $json_data = json_decode($json, true);
      $type = ucfirst($_POST['type']);
    }
    // Define the output file, specifiy filename & type
    $fp = fopen('php://output', 'w');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$character.'.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Prepare headers for CSV file
    $headers = array('Character','Data Type','Name','Description','Published');

    // Check $fb has correctly been assigned
    if ($fp) {
        // Include headers in CSV
        fputcsv($fp, $headers);

        // Loop through the JSON array & populate rows with associated data
        foreach ($json_data['data']['results'] as $key => $data) {

          // Switch for datea as it is formatted differently throughout
          if ($_POST['type'] == "comics") {$published = $data['dates'][0]['date']; }
          if ($_POST['type'] == "events") {$published = $data['start']; }
          if ($_POST['type'] == "series") {$published = $data['startYear']; }
          if ($_POST['type'] == "stories") {$published = $data['modified']; }

          // The row of data to be inserted
          $row = array('Character'=> $_POST['character'],'Data Type'=>$type,'Name'=>$data['title'],'Description'=>$data['description'],'Published'=>$published);

          // Initiate row inclusion
          fputcsv($fp, $row);
        }
        // Do until completed & return to script
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title></title>
<meta name="description" content="">
<meta name="viewport" content="width=device-width">
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="handlebars-v1.3.0.js"></script>
<link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css"></script>
</head>
<body>
  <div class="container">
	<div class="col-lg-4">
    <div class="panel panel-default">
      <div class="panel-heading"><h3>Search Character</h3></div>
      <div class="panel-body">
        <form action="<?php $_SERVER['PHP_SELF']?>" method="POST" enctype="multipart/form-data" class="form-horizontal">

            <div class="control-group">
              <label class="control-label">Character :</label>
              <div class="controls">
                <input type="text" name="character" id="character" value="<?php if (isset($_POST['character'])) { echo $_POST['character']; }?>" class="" required>
              </div>
            </div>
            <div class="control-group">
              <div class="controls">
              <label class="control-label">Comics :</label>
                <input type="radio" name="type" value="comics" checked>
              </div>
            </div>
            <div class="control-group">
              <div class="controls">
              <label class="control-label">Events :</label>
                <input type="radio" name="type"  value="events">
              </div>
            </div>
            <div class="control-group">
              <div class="controls">
              <label class="control-label">Series :</label>
                <input type="radio" name="type"  value="series">
              </div>
            </div>
            <div class="control-group">
              <div class="controls">
              <label class="control-label">Story :</label>
                <input type="radio" name="type"  value="stories" >
              </div>
            </div>

            <div class="controls">
              <button type="submit" name="submit" class="btn btn-success">Search</button>
              <button type="submit" name="export" class="btn btn-success">Export</button>
          	</div>
        </form>
      </div>
    </div>

    </div>
    <div class="col-lg-8">
      <div class="panel panel-default">
        <div class="panel-heading"><h3>Result</h3></div>
        <div class="panel-body">
          <table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
                <tr>
                  <th>Character</th>
                  <th>Data Type</th>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Date First Published</th>
                </tr>
            </thead>
            <tbody>
              <?php if (isset($_POST['character'])) { ?>
            	<?php $i = 0; foreach ($json_data['data']['results'] as $key => $data) {?>
              <tr>
                  <td><?php echo ucfirst($_POST['character']); ?></td>
                  <td><?php echo $type; ?></td>
                  <td><?php echo $data['title']; ?></td>
                  <td><?php if (isset($data['description'])) {echo $data['description'];} else { echo "No Description"; } ?></td>
                  <?php 
                    if ($_POST['type'] == "comics") {$published = $data['dates'][0]['date']; }
                    if ($_POST['type'] == "events") {$published = $data['start']; }
                    if ($_POST['type'] == "series") {$published = $data['startYear']; }
                    if ($_POST['type'] == "stories") {$published = $data['modified']; }
                  ?>
                  <td><?php echo date('d/m/Y',strtotime($published)); ?></td>
              </tr>
              <?php $i++; }} else { echo ""; } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
      </div>
</body>
</html>

<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript">
  $(document).ready(function(){

    $('#example').DataTable();

  });
</script>
