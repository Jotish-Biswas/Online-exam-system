@extends('layouts.app')

@section('title', 'Student Login')

@section('nav-items')
    <span class="nav-link text-white">
        <i class="fas fa-user-graduate me-1"></i>Student Portal
    </span>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-center bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-graduate me-2"></i>
                    Student Exam Access
                </h4>
            </div>
            <div class="card-body p-4">
                <p class="text-center text-muted mb-4">
                    Enter your details to access the exam
                </p>
                
                <form action="{{ route('student.authenticate') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="exam_id" class="form-label">
                            <i class="fas fa-id-card me-1"></i>
                            Exam ID
                        </label>
                        <input type="text" 
                               class="form-control @error('exam_id') is-invalid @enderror" 
                               id="exam_id" 
                               name="exam_id" 
                               value="{{ old('exam_id') }}" 
                               placeholder="Enter exam ID provided by your instructor"
                               required>
                        @error('exam_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="student_id" class="form-label">
                            <i class="fas fa-user me-1"></i>
                            Student ID
                        </label>
                        <input type="text" 
                               class="form-control @error('student_id') is-invalid @enderror" 
                               id="student_id" 
                               name="student_id" 
                               value="{{ old('student_id') }}" 
                               placeholder="Enter your student ID"
                               required>
                        @error('student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="index_no" class="form-label">
                            <i class="fas fa-hashtag me-1"></i>
                            Index Number
                        </label>
                        <input type="text" 
                               class="form-control @error('index_no') is-invalid @enderror" 
                               id="index_no" 
                               name="index_no" 
                               value="{{ old('index_no') }}" 
                               placeholder="Enter your index number"
                               required>
                        @error('index_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>
                            Access Exam
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center text-muted">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    All fields are required to access the exam
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
