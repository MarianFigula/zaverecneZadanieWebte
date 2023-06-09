<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use \Illuminate\Support\Str;
use PHPUnit\Exception;
use App\Http\Controllers\OctaveController;

class ProblemController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getStringInCurlyBraces($string): string
    {
        $start = strpos($string, '{') + 1;
        $length = strpos($string, '}') - $start;
        $substring = substr($string, $start, $length); // "B34A5A"
        return $substring;
    }


    public function regexEquation($string)
    {
        //$pos = strpos( $string, '$');
        $string = preg_replace('/\$/', '\[', $string, 1);
        $string = preg_replace('/\$/', '\]', $string, 1);
        return $string;
    }


    function fetchData()
    {

        $student_id = session('student_id');
        $file_id = session('file_id');
        $path = session('path');

        $latexLines = $this->getLinesFromFile($path);
        $task_num = $this->getTaskNumber($latexLines);

        $this->updateTaskNumberInDatabase($task_num, $file_id, $student_id);
        $data = $this->parseLatexData($latexLines, $task_num);
        //var_dump($data);

        return view('problem', ['latexLines' => $latexLines, 'src' => $data[0], 'correctAnswer' => $data[1], 'task' => $data[2],
            'student_id' => $student_id, 'file_id' =>$file_id]);
    }

    function updateTaskNumberInDatabase($task_num, $file_id, $student_id)
    {
        //var_dump($task_num);
        DB::update("update student_tasks as st SET st.task_num ='" . $task_num . "' where st.file_id=" . $file_id . ' and st.student_id=' . $student_id);
    }

    // TODO FIX
    static function updateAnswerInDatabase($studentAnswer, $correctAnswer)
    {

        $student_id = session('student_id');
        $file_id = session('file_id');

        //var_dump($studentAnswer);

        if ($correctAnswer === $studentAnswer){
            $isCorrect = true;
        }else{
            $isCorrect = false;
        }
        var_dump($file_id);
        var_dump($student_id);
        var_dump($correctAnswer);

        DB::table('student_tasks')
            ->where(['student_id'=>$student_id,'file_id'=> $file_id])// Replace 'my_table' with your table name and '$id' with the ID of the row you want to update
            ->update([
                'task_correct' => $isCorrect,  // Replace 'column1' with the name of the column you want to update and 'New Value 1' with the new value
                'student_answer' => $studentAnswer,  // Replace 'column2' with the name of the column you want to update and 'New Value 2' with the new value
                // Add more columns here as needed
            ]);

        return redirect()->route('studentstats');
    }

    function getTaskNumber($latexLines): string
    {

        $numberOfProblems = $this->getNumberOfProblems($latexLines);

        $random = rand(1, $numberOfProblems - 1);
        $count = 0;
        foreach ($latexLines as $line) {
            if (STR::contains($line, "\section*{")) {
                if ($count == $random) {
                    return self::getStringInCurlyBraces($line);
                }
                $count++;
            }
        }
        return "";
    }

    function getLinesFromFile($path): array
    {

        // TODO: tu bude path
        $filePath = public_path('priklady/'.$path[0]->path);

        try {
            $latexContent = File::get($filePath);
            //$latexContent = str_replace('$', '', $latexContent);
            $latexLines = explode("\n", $latexContent);
            return $latexLines;
        } catch (Exception $e) {
            return [];
        }

    }

    public function getNumberOfProblems($latexLines): int
    {
        $count = 0;
        foreach ($latexLines as $line) {
            if (STR::contains($line, "\section*{")) {
                $count++;
            }
        }
        return $count;

    }

    function parseLatexData($latexLines, $task_num)
    {
        global $correctAnswer;

        $taskFound = false;
        $solutionFound = false;
        $solutionEquationFound = false;
        $assignmentFound = false;
        $assignmentEquationFound = false;
        $src = "";
        $correctAnswer = "";
        $task = "";
        foreach ($latexLines as $line) {
            $line = str_replace('\\\\', '', $line);
            if (Str::contains($line, $task_num)) {
                $taskFound = true;
                continue;
            }
            if ($taskFound) {

                if (Str::contains($line,'\begin{task}')) {
                    $assignmentFound = true;
                    continue;
                }

                if($assignmentFound) {
                    if (Str::contains($line,'\end{task}')) {
                        $assignmentFound = false;
                        continue;
                    }
                    if (Str::contains($line,'\end{equation*}')) {
                        continue;
                    }
                    if (Str::contains($line,'\begin{equation*}')) {
                        $assignmentEquationFound = true;
                        continue;
                    }
                    if($assignmentEquationFound){
                        $task .= '\['.$line.'\]';
                        $assignmentEquationFound = false;
                        continue;
                    }
                    $pattern = '/\$(.*?)\$/';
                    preg_match_all($pattern, $line, $matches);

                    if (!Str::contains(trim($line),"\\") && !Str::contains(trim($line),"$")) {
                        $task = $task . $line;
                        continue;
                    }

                    if (count($matches[1]) != 0) {
                        for ($x = 0; $x < count($matches[1]); $x++){
                            $substring = '\['.$matches[1][$x].'\]';
                            $line = preg_replace($pattern, $substring, $line, 1);
                        }
                        $task = $task . $line;
                        continue;
                    }

                    if (empty(trim($line))) {
                        continue;
                    }
                    if (Str::contains($line, "\includegraphics")) {
                        $src = $this->getStringInCurlyBraces($line);
                        continue;
                    }

                }

                if($assignmentEquationFound){
                    $equation = '\['.$line.'\]';
                    $assignmentEquationFound = false;
                    continue;
                }
                $pattern = '/\$(.*?)\$/';
                preg_match($pattern, $line, $matches);


                if (isset($matches[1])) {
                    $substring = $matches[1];
                    $equation = '\['.$substring.'\]';
                    continue;
                }

                if (Str::contains($line, "\includegraphics")) {
                    $src = $this->getStringInCurlyBraces($line);
                    continue;
                }
                if (Str::contains($line,'\begin{solution}')) {
                    $solutionFound = true;
                }
                if ($solutionFound){
                    if (Str::contains($line,'\begin{equation*}')) {
                        $solutionEquationFound = true;
                    }
                }
                if ($solutionEquationFound){
                    if (!Str::contains($line,'\begin{equation*}')) {
                        $correctAnswer = trim($line);
                        $solutionEquationFound = false;
                        $solutionFound = false;
                        $taskFound = false;
                    }
                }
            }
        }
        return [$src, $correctAnswer, $task];

    }

    public function solve(Request $request){
        dd($request);
    }

}

