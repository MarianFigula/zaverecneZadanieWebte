<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class StudentFilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $student_id = auth()->user()->id;
        $query = DB::select('select f.id,f.path,f.points,f.date, st.task_gen, st.task_correct from student_tasks st join files f on st.file_id = f.id where st.student_id='.$student_id);

        $data = compact('query','student_id');

        return view('studentfiles')->with($data);

    }

    public function update( Request $request){

        $student_id = $request->student_id;
        $file_id = $request->file_id;

        DB::update('update student_tasks st SET st.task_gen=true where st.file_id='.$file_id.' and st.student_id='.$student_id);
        $path = DB::select('select f.path from files f where id='.$file_id);

        //$data = compact('student_id','file_id','path');

        //return view('problem')->with($data);//latex
        //return ProblemController::fetData($student_id, $file_id, $path);
        session(['student_id' => $student_id, 'file_id' => $file_id, 'path' => $path]);

        return redirect()->route('problem');

    }
}
