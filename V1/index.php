<?php
include '../dbconnect.php';

$result = '';
$model = '';
$query = '';
$last_question = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture the user's input from the prompt
    $user_input = htmlspecialchars($_POST['query']);
    $model = $_POST['model'];

    // Store the last question
    $last_question = $user_input;

    // Set the @query variable as per the requirement
    $query = "SET @query=\"$user_input\";";
    $conn->query($query);

    // Load the appropriate LLM model based on the user's selection
    if ($model === 'Mistral') {
        $conn->query("CALL sys.ML_MODEL_LOAD('mistral-7b-instruct-v1', NULL);");
        $sql = "SELECT sys.ML_GENERATE(@query, JSON_OBJECT('task', 'generation', 'model_id', 'mistral-7b-instruct-v1')) AS result;";
    } elseif ($model === 'LLama2') {
        $conn->query("CALL sys.ML_MODEL_LOAD('llama2-7b-v1', NULL);");
        $sql = "SELECT sys.ML_GENERATE(@query, JSON_OBJECT('task', 'generation', 'model_id', 'llama2-7b-v1')) AS result;";
    }

    // Execute the query and fetch the result
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            // Clean up the result to remove {"text": " and "}
            $raw_result = $row['result'];
            $cleaned_result = preg_replace('/^\{"text":\s*"|"\}$/', '', $raw_result);
            
            // Replace both \n\n and \n with actual line breaks
            $formatted_result = str_replace(['\n\n', '\n'], ['<br><br>', '<br>'], $cleaned_result);
            
            // Ensure capitalization in the generated text
            $capitalized_result = ucfirst_sentence($formatted_result);

            // Apply nl2br to ensure any other single \n are also converted
            $result = nl2br($capitalized_result);
        }
    } else {
        $result = "No result found for the query.";
    }
}

// Function to capitalize the first letter of each sentence
function ucfirst_sentence($text) {
    // Use a regular expression to find sentences and ensure they start with an uppercase letter
    $text = preg_replace_callback('/([.!?]\s*|\A\s*)([a-z])/', function($matches) {
        return $matches[1] . strtoupper($matches[2]);
    }, $text);
    return $text;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Query App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <label for="query">Prompt:</label>
            <input type="text" id="query" name="query" value="<?php echo htmlspecialchars($last_question); ?>" required>

            <label for="model">Select Model:</label>
            <select id="model" name="model" required>
                <option value="Mistral" <?php if ($model === 'Mistral') echo 'selected'; ?>>Mistral</option>
                <option value="LLama2" <?php if ($model === 'LLama2') echo 'selected'; ?>>LLama2</option>
            </select>

            <button type="submit">Submit</button>
        </form>

        <?php if ($last_question): ?>
        <div class="last-question">
            <p><strong>Last Question Asked:</strong> <?php echo htmlspecialchars($last_question); ?></p>
        </div>
        <?php endif; ?>

        <div class="result-box">
            <p>Result of the query:</p>
            <div class="result"><?php echo $result; ?></div>
        </div>
    </div>
</body>
</html>