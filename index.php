<?php 
include "partials/header.php";
include "partials/notification.php";
include "config/Database.php";
include "classes/Pdf.php";
require 'vendor/autoload.php';
session_start();

$database = new Database();
$db = $database->connect();

// $uploaded_file = ''; 
// $file_path = '';
$pagesArr = [];
$nl = "<br>";

if($_SERVER['REQUEST_METHOD'] === "POST") {
    
    if(isset($_POST['convert_file'])) {
        $file_type = '';
        
        // Get uploaded file
        $uploaded_file = $_FILES['uploaded_file'];
        // var_dump($uploaded_file['type']);
        // Get file type. Later used in the 'Converted Text' section on the webpage - 'application/pdf'
        $file_type = $uploaded_file['type'];
        // Get file's temporary storage location
        $file_path = $uploaded_file['tmp_name'];
        // echo $file_path;
        // Get file name and then remove the .pdf extension (May need this later on)
        $filename = pathinfo($uploaded_file['name'], PATHINFO_FILENAME);

        // Convert pdf file  to text
        if($uploaded_file['type'] === 'application/pdf') {
    
            // Use Smalot PDF Parser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
    
            // Get an array of the all pages from pdf
            $pagesArr = $pdf->getPages();

        }
        
        // Convert epub files to text
        elseif($uploaded_file['type'] === 'application/epub+zip') {
            // var_dump(class_exists('ZipArchive'));
            $epub = new ZipArchive;
            // var_dump($epub);
            $text = '';
            if ($epub->open($file_path) === TRUE) {
                // var_dump($epub->statIndex(1));
                // var_dump($epub->getFromIndex(11));
                for ($i = 0; $i < $epub->numFiles; $i++) {
                    $file = $epub->statIndex($i);

                    // XHTML content is usually in OEBPS/*.xhtml or *.html
                    if (preg_match('/\.(xhtml|html)$/', $file['name'])) {
                        $content = $epub->getFromIndex($i);
                        // $text .= strip_tags("PAGE-" . $i + 1 . " " . $content); // remove HTML
                        // $text .= "<br><br>";
                        $pagesArr[] = strip_tags($content); // remove HTML
                    }
                }
                $epub->close();
            }

        }
        // If file type is not pdf or epub
        else {
            echo "File type not supported.";
            $pagesArr = [];
        }


    }


}
?>

<h1>Speed Reader 2</h1>

<!-- Upload file to convert to text -->
<form action="" method="post" enctype="multipart/form-data">
    <label for="file"><strong>Add a doc</strong></label><br><br>
    <input type="file" name="uploaded_file" id="uploaded_file" name="uploaded_file"><br><br>
    <input type="submit" name="convert_file" value="Convert file">
</form>

<?php 

// Only show .options if a file has been successfully uploaded
// Check if pdf or epub has been uploaded with no errors
if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == UPLOAD_ERR_OK) {

?>

    <div class="options">
        <h3>Options</h3>
        <div>
            <!-- Opens reading interface -->
            <div class="start-reading btn">Start Reading</div>
        </div>
    </div>

<?php } ?>



<div class="reading-area">
    <!-- .reading-area is hidden until .start reading is pressed -->

    <h2>Controls</h2>

    <!-- Reading controls container -->
    <div class="reading-controls">
        <!-- Go Back - one word -->
        <div class="backwards btn" onclick="backward()">Back</div>
        <!-- start/stop -->
        <div class="start-stop-reading btn" onclick="read()">Start/Stop</div> 
        <!-- Go forward - one word  -->
        <div class="forward btn" onclick="forward()">Forward</div>
        <!-- Reading Speed -->
        <div class="speed">
            <select name="speed" id="">
                <option value="">Reading speed - 100 wpm</option>
                <option value="">Reading speed - 150 wpm</option>
                <option value="">Reading speed - 200 wpm</option>
                <option value="">Reading speed - 250 wpm</option>
                <option value="">Reading speed - 300 wpm</option>
                <option value="">Reading speed - 350 wpm</option>
                <option value="">Reading speed - 400 wpm</option>
            </select>
        </div> 
        <!-- Close Reading area -->
        <div class="close-reading btn">X</div>

        <!-- Word currently being read -->
        <div class="current-word"></div>
    </div>


</div>



<!-- Display file as editable text -->
<?php if($pagesArr) { ?>

    <h2>Converted Text</h2>
    <div class='textarea' contenteditable>
        <?php 
            $pagesArrIndex = 0;

            foreach($pagesArr as $page) {
                $pagesArrIndex++;
                // PDFs
                if ($file_type === 'application/pdf') {
                    $unclean_text = $page->getText(); 
                    // Only show letters, numbers, punctuation (.,!?), and spaces
                    $text = preg_replace('/[^a-zA-Z0-9 .,!?\-]/', '', $unclean_text);
                    echo $text;
                }
                // EPUBs
                elseif ($file_type == 'application/epub+zip') {
                    echo $page;
                }
            }
         ?>
    </div>

<?php } else { ?>
    <p>Upload your pdf or epub document to get started.</p>

<?php } ?>  


<script>

    // JS for opening and closing .reading-area
    const readingArea = document.querySelector('.reading-area');
    const startReading = document.querySelector('.start-reading');
    const closeReading = document.querySelector('.close-reading');
    const textarea = document.querySelector('.textarea')
    const currentWordDiv = document.querySelector('.current-word');

    startReading.addEventListener('click', () => {
        // console.log('start reading');
        readingArea.classList.add('reading-area-open');
    })

    closeReading.addEventListener('click', () => {
        // console.log('close reading');
        readingArea.classList.remove('reading-area-open');
    })

    //////////////////////////////////////////////////////////////////////////////////////////////

    // Store text from file in a JS variable
    const text = document.querySelector('.textarea').innerHTML; 

    // Split text into an array of words and remove spaces at beginning and end of text
    let textArr = text.trim().split(/\s+/);
    console.log(textArr);

    // Get word and word position to display on .reading-area
    let currentWordIndex = 0;
    let currentWord = textArr[currentWordIndex];

    // Display currentWord as the first word in the text
    currentWordDiv.innerHTML = currentWord;

    // Reading status - PAUSED or READING
    let readingStatus = "PAUSED";

    // Used to manage setInterval in read()
    let reading;

    // Pause reading
    let pauseReading = () => {
        readingStatus = "PAUSED";
        clearInterval(reading);
    }


    // Start and Stop Reading function 
    let read = () => {

        if(readingStatus === "PAUSED") {
            readingStatus = "READING";

            // Start moving through textArr displaying the words that match the currentWordIndex index 
            reading = setInterval(() => {
                currentWord = textArr[currentWordIndex];
                currentWordDiv.innerHTML = currentWord;
                currentWordIndex ++;
                
                // Stop interval when at the end of the book. 
                if(currentWordIndex >= textArr.length) {
                    clearInterval(reading);
                    readingStatus = "PAUSED";
                    console.log("The end.");
                    currentWordIndex = textArr.length;
                    console.log("Last word: " + currentWord + " - " + currentWordIndex);
                    currentWordDiv.innerHTML = currentWord;
                }
                
                
                return currentWordIndex;
            }, 250);

        // Pause reading if it's already running
        } else if (readingStatus === "READING") {
            pauseReading();
        } 
             
    }

    let backward = () => {
        currentWord = textArr[currentWordIndex];
        // Go to previous word if currentWordIndex is greater than 0 
        if(currentWordIndex > 0) {
            currentWordIndex --;
            console.log(currentWordIndex);
            console.log("backwards");
        }
        if (readingStatus === "READING") {
            pauseReading();
        } 
        currentWordDiv.innerHTML = currentWord;
    }

    let forward = () => {
        currentWord = textArr[currentWordIndex];
        // Go to next word if currentWordIndex is less than the length of the textArr 
        if(currentWordIndex < textArr.length) {
            currentWordIndex ++;
            console.log(currentWordIndex);
            console.log("forward");
        }
        if (readingStatus === "READING") {
            pauseReading();
        } 
        currentWordDiv.innerHTML = currentWord;
    }

    
    

    /////////////////////////////////////////////////////////////////////////////////////////////


</script>


<?php include "partials/footer.php"; ?>

    
