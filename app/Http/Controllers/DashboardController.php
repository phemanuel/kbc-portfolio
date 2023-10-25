<?php

namespace App\Http\Controllers;
use App\Models\userRoles;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\UserEducation;
use App\Models\UserExperience;
use App\Models\UserService;
use App\Models\Countries;
use App\Models\UserPortfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    //
    public function index()
    {
        $countries = Countries::all();
        return view('dashboard.dashboard', compact('countries'));        
    }
    
    public function userRole()
    {
        $allRoles = UserRoles::all(); // Retrieve all roles from the database
        $user = auth()->user(); // Get the authenticated user
        $userRoles = explode(',', $user->user_roles); // Convert the user's saved roles to an array

        return view('dashboard.user-roles')->with([
            'allRoles' => $allRoles,
            'userRoles' => $userRoles,
        ]);
    }

    public function userRoleSave(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'user_roles' => 'required|array|valid_roles|max_roles:6',
            ], [
                'user_roles.valid_roles' => 'One or more selected roles are invalid.',
            ],
                [
                    'user_roles.max_roles' => 'You cannnot select more than 6 roles.',                
            ]);
            
            // Process the selected roles and save them in the database
            $userRoles = implode(',', $validatedData['user_roles']);

            $user = User::findOrFail($id);
            $user->user_roles = $userRoles;
            $user->save();
        
            return redirect()->route('user-role')->with('success', 'Roles update successful.');
        } catch (\Exception $e) {
            // Handle the exception, log the error, and return with an error message
            //$errorMessage = 'Error-save user role: ' . $e->getMessage();
            $errorMessage = 'You cannot select more than 6 roles.';
            Log::error($errorMessage);
            
            return redirect()->route('user-role')->with('error', $errorMessage);
        }
    }

    public function userSkill()
    {
        try {
            $user = auth()->user();
            $user_id = $user->id;    
            
            $userSkills = UserSkill::where('user_id', $user_id)
            ->paginate(5);
    
            return view('dashboard.user-skills', compact('userSkills'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-load user skill: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('user-skill')->with('error', 'An error occurred while retrieving user skills.');
        }
    
    }

    public function userSkillSave(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'user_skill' => 'required|string',
                'user_skill_level' => 'required|string',
            ]);            

            $user = UserSkill::create([
                'user_id' => $validatedData['user_id'],
                'user_skill' => $validatedData['user_skill'],
                'user_skill_level' => $validatedData['user_skill_level'],
            ]); 

            return redirect()->route('user-skill')->with('success', 'Skill added successfully.');
        } catch (ValidationException $e) {
            // Validation failed. Redirect back with validation errors.
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Log the error
            $errorMessage = 'Error-save user skill: ' . $e->getMessage();
            Log::error($errorMessage);

            return redirect()->back()->with('error', 'An error occurred during skill update. Please try again.');
        }
    }

    public function deleteUserSkill($id)
    {
        try {
            $userSkill = UserSkill::findOrFail($id);
            $userSkill->delete();

            return redirect()->route('user-skill')->with('success-new', 'Skill deleted successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-delete user skill: ' . $e->getMessage();
            Log::error($errorMessage);
            return redirect()->route('user-skill')->with('error-new', 'Failed to delete skill.');
        }
    }

    public function editUserSkill($id)
    {
        try {  
            $userSkill = UserSkill::findOrFail($id);
    
            return view('dashboard.edit-user-skill', compact('userSkill'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-edit user skill: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('edit-user-skill')->with('error', 'An error occurred while retrieving user skill.');
        }
    }

    public function updateUserSkill(Request $request, $id)
    {
        try {
            // Validate the request data as needed
            $validatedData = $request->validate([                
                'user_skill' => 'required|string',
                'user_skill_level' => 'required|string',                
            ]);

            // Retrieve the user skill based on the $id
            $userSkill = UserSkill::findOrFail($id);

            // Update the user skill attributes based on the request data
            $userSkill->update([
                'user_skill' => $validatedData['user_skill'],
                'user_skill_level' => $validatedData['user_skill_level'],
                // Add more fields if necessary
            ]);

            // Redirect to the user's skill list or another appropriate page
            return redirect()->route('user-skill')->with('success-new', 'Skill updated successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-update user skill: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle any exceptions or errors here
            return back()->with('error', 'An error occurred while updating the skill. Please try again.');
        }
    }

    public function userEducation()
    {
        try {
            $user = auth()->user();
            $user_id = $user->id;    
            
            $userEducations = UserEducation::where('user_id', $user_id)
            ->paginate(4);
    
            return view('dashboard.user-education', compact('userEducations'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-load user education: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('user-education')->with('error', 'An error occurred while retrieving user education history.');
        }
        
    }

    public function userEducationSave(Request $request)
    {
        $user = auth()->user();

        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'college_name' => 'required|string|max:255',
                'college_year' => 'required|string',
                'college_qualification' => 'required|string|max:255',
                'college_certificate' => 'nullable|mimes:pdf',
            ]);            

            if ($request->hasFile('college_certificate')) {
                $userCertificateFile = $request->file('college_certificate');
        
                $username = $user->user_name; // Get the user's username
        
                // Generate filenames 
                $collegeYear = $validatedData['college_year'];
                $collegeQualification = substr($validatedData['college_qualification'], 0, 5);
                $collegeName = substr($validatedData['college_name'], 0, 5);
                //----                
                $collegeName = str_replace(' ', '', $collegeName);
                $collegeQualification = str_replace(' ', '', $collegeQualification);
                $userCertificateFilename = $username . '_'. $collegeYear . '_' . $collegeQualification . '_' . $collegeName . '.' . $userCertificateFile->getClientOriginalExtension();
                
                // Store file
                $certificatePath = $userCertificateFile->storeAs('certificates', $userCertificateFilename, 'public');
                } else {
                $certificatePath = null; // If no file was uploaded
            }
            
            $userEducation = UserEducation::create([
                'user_id' => $validatedData['user_id'],
                'college_name' => $validatedData['college_name'],
                'college_year' => $validatedData['college_year'],
                'college_qualification' => $validatedData['college_qualification'],
                'college_certificate' => $certificatePath,
            ]); 

            return redirect()->route('user-education')->with('success', 'Education/Certification added successfully.');
        } catch (ValidationException $e) {
            // Validation failed. Redirect back with validation errors.
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Log the error
            $errorMessage = 'Error-save user education: ' . $e->getMessage();
            Log::error($errorMessage);

            return redirect()->back()->with('error', 'An error occurred during Education/Certification update. Please try again.');
        }

    }  

    public function editUserEducation($id)
    {
        try {  
            $userEducations = UserEducation::findOrFail($id);
    
            return view('dashboard.edit-user-education', compact('userEducations'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-edit user education: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('edit-user-education')->with('error', 'An error occurred while retrieving user education history.');
        }
    }    

    public function updateUserEducation(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'college_name' => 'required|string',
                'college_year' => 'required|string',
                'college_qualification' => 'required|string',
                'college_certificate' => 'nullable|mimes:pdf', 
            ]);

            // Retrieve the user education based on the $id
            $userEducation = UserEducation::findOrFail($id);

            if ($request->hasFile('college_certificate')) {
                // Handle the new certificate file
                $userCertificateFile = $request->file('college_certificate');
                $user = auth()->user();
                $username = $user->user_name; // Get the user's username

                // Generate filenames 
                $collegeYear = $validatedData['college_year'];
                $collegeQualification = substr($validatedData['college_qualification'], 0, 5);
                $collegeName = substr($validatedData['college_name'], 0, 5);
                //----                
                $collegeName = str_replace(' ', '', $collegeName);
                $collegeQualification = str_replace(' ', '', $collegeQualification);
                $userCertificateFilename = $username . '_'. $collegeYear . '_' . $collegeQualification . '_' . $collegeName . '.' . $userCertificateFile->getClientOriginalExtension();
                
                // Store file
                $certificatePath = $userCertificateFile->storeAs('certificates', $userCertificateFilename, 'public');
                
                // Update the certificate field in the database
                $userEducation->college_certificate = $certificatePath;
            }

            // Update other education attributes based on the request data
            $userEducation->update([
                'college_name' => $validatedData['college_name'],
                'college_year' => $validatedData['college_year'],
                'college_qualification' => $validatedData['college_qualification'],
            ]);

            // Redirect to the user's education list or another appropriate page
            return redirect()->route('user-education')->with('success', 'Education/Certification updated successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-update user education: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle any exceptions or errors here
            return back()->with('error', 'An error occurred while updating the Education/Certification. Please try again.');
        }
    }


    public function deleteUserEducation($id)
    {
        try {
            $userEducation = UserEducation::findOrFail($id);
            $userEducation->delete();

            return redirect()->route('user-education')->with('success-new', 'Education/Certification deleted successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-delete user education: ' . $e->getMessage();
            Log::error($errorMessage);
            return redirect()->route('user-education')->with('error-new', 'Education/Certification.');
        }
    }

    public function userExperience()
    {
        try {
            $user = auth()->user();
            $user_id = $user->id;    
            
            $userExperiences = UserExperience::where('user_id', $user_id)
            ->orderBy('company_year', 'desc')
            ->paginate(5);
    
            return view('dashboard.user-experience', compact('userExperiences'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-load user experience: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('user-experience')->with('error', 'An error occurred while retrieving user education history.');
        }
    }

    public function userExperienceSave(Request $request)
    {
        
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'user_company' => 'required|string|max:255',
                'company_year' => 'required|string',
                'user_company_role' => 'required|string',
                'user_about_role' => 'required|string|max_words:50',
            ], [
                'user_about_role.max_words' => 'The "About Role" field cannot exceed 80 words.',
            ]);
            
            // return response()->json([
            //     'status_code' => 200,
            // ]);
            $userExperience = UserExperience::create([
                'user_id' => $validatedData['user_id'],
                'user_company' => $validatedData['user_company'],
                'company_year' => $validatedData['company_year'],
                'user_company_role' => $validatedData['user_company_role'],
                'user_about_role' => $validatedData['user_about_role'],
            ]);
    
             return redirect()->route('user-experience')->with('success', 'Work experience added successfully.');
        } catch (ValidationException $e) {
            // Validation failed. Redirect back with validation errors.
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Log the error
            $errorMessage = 'Error-save user experience: ' . $e->getMessage();
            Log::error($errorMessage);

            return redirect()->route('user-experience')->with('error', $errorMessage);
           
            //return redirect()->back()->with('error', 'An error occurred during work experience update. Please try again.');
        }

    }

    public function deleteUserExperience($id)
    {
        try {
            $userExperience = UserExperience::findOrFail($id);
            $userExperience->delete();

            return redirect()->route('user-experience')->with('success-new', 'work experience deleted successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-delete user experience: ' . $e->getMessage();
            Log::error($errorMessage);
            return redirect()->route('user-experience')->with('error-new', 'work experience.');
        }
    }

    public function editUserExperience($id)
    {
        try {  
            $userExperiences = UserExperience::findOrFail($id);
    
            return view('dashboard.edit-user-experience', compact('userExperiences'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-edit user experience: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('edit-user-experience')->with('error', 'An error occurred while retrieving user work experience.');
        }
    }   

    public function updateUserExperience(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'user_company' => 'required|string|max:255',
                'company_year' => 'required|string',
                'user_company_role' => 'required|string',
                'user_about_role' => 'required|string|max_words:50',
            ], [
                'user_about_role.max_words' => 'The "About Role" field cannot exceed 80 words.',
            ]);

            // Retrieve the user education based on the $id
            $userExperience = UserExperience::findOrFail($id);            

            // Update other education attributes based on the request data
            $userExperience->update([
                'user_company' => $validatedData['user_company'],
                'company_year' => $validatedData['company_year'],
                'user_company_role' => $validatedData['user_company_role'],
                'user_about_role' => $validatedData['user_about_role'],
            ]);

            // Redirect to the user's education list or another appropriate page
            return redirect()->route('user-experience')->with('success', 'work experience updated successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-update user experience: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle any exceptions or errors here
            return back()->with('error', 'An error occurred while updating the work experience. Please try again.');
        }
    }  

    public function userService()
    {
        try {
            $user = auth()->user();
            $user_id = $user->id;    
            
            $userServices = UserService::where('user_id', $user_id)
            ->orderBy('user_service', 'desc')
            ->paginate(5);
    
            return view('dashboard.user-service', compact('userServices'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-load user service: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('user-service')->with('error', 'An error occurred while retrieving user service history.');
        }
    }

    public function userServiceSave(Request $request)
    {
        
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',  
                'user_service' => 'required|string',               
                'user_service_description' => 'required|string|max_words:30',
            ], [
                'user_service_description.max_words' => 'The "About Service" field cannot exceed 30 words.',
            ]);
          
            $userService = UserService::create([
                'user_id' => $validatedData['user_id'],
                'user_service' => $validatedData['user_service'],
                'user_service_description' => $validatedData['user_service_description'],
            ]);
    
             return redirect()->route('user-service')->with('success', 'Service added successfully.');
        } catch (ValidationException $e) {
            // Validation failed. Redirect back with validation errors.
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Log the error
            $errorMessage = 'Error-save user service: ' . $e->getMessage();
            Log::error($errorMessage);

            return redirect()->route('user-service')->with('error', $errorMessage);
           
            //return redirect()->back()->with('error', 'An error occurred during work experience update. Please try again.');
        }

    }

    public function deleteUserService($id)
    {
        try {
            $userService = UserService::findOrFail($id);
            $userService->delete();

            return redirect()->route('user-service')->with('success-new', 'service deleted successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-delete user service: ' . $e->getMessage();
            Log::error($errorMessage);
            return redirect()->route('user-service')->with('error-new', 'service.');
        }
    }

    public function editUserService($id)
    {
        try {  
            $userServices = UserService::findOrFail($id);
    
            return view('dashboard.edit-user-service', compact('userServices'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-edit user service: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('edit-user-service')->with('error', 'An error occurred while retrieving user services.');
        }
    }   

    public function updateUserService(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'user_id' => 'required|integer',  
                'user_service' => 'required|string',               
                'user_service_description' => 'required|string|max_words:30',
            ], [
                'user_service_description.max_words' => 'The "About Service" field cannot exceed 30 words.',
            ]);

            // Retrieve the user education based on the $id
            $userService = UserService::findOrFail($id);            

            // Update other education attributes based on the request data
            $userService->update([
                'user_service' => $validatedData['user_service'],
                'user_service_description' => $validatedData['user_service_description'],
            ]);

            // Redirect to the user's education list or another appropriate page
            return redirect()->route('user-service')->with('success', 'service updated successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-update user service: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle any exceptions or errors here
            return back()->with('error', 'An error occurred while updating the service. Please try again.');
        }
    }  

    public function userPortfolio()
    {
        try {
            $user = auth()->user();
            $user_id = $user->id;    
            
            $userPortfolios = UserPortfolio::where('user_id', $user_id)
            ->paginate(5);
    
            return view('dashboard.user-portfolio', compact('userPortfolios'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-load user portfolio: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('user-portfolio')->with('error', 'An error occurred while retrieving user project history.');
        }
        
    }

    public function userPortfolioSave(Request $request)
    {
        $user = auth()->user();
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|integer',
                'file_name' => 'required|string',
                'file_type' => 'required|string',
                'file_description' => 'required|string',
                'file' => 'required|mimes:pdf,jpeg,png',
            ]);            

            if ($request->hasFile('file')) {
                $userFile = $request->file('file');
        
                $username = $user->user_name; // Get the user's username
        
                // Generate filenames                
                $userFilename = $user->user_name .'_'. uniqid(). '_' . substr(uniqid(), 0, 5) . '.' . $userFile->getClientOriginalExtension();
                
                //Store file
                $file_type = $validatedData['file_type'];                
                if($file_type=='Image'){
                    $filePath = $userFile->storeAs('portfolio/image', $userFilename, 'public');
                } 
                elseif($file_type=='Document') {
                    $filePath = $userFile->storeAs('portfolio/document', $userFilename, 'public');
                }
            }
                else {
                $filePath = null; // If no file was uploaded
            }
            
            $userPortfolio = UserPortfolio::create([
                'user_id' => $validatedData['user_id'],
                'file_name' => $validatedData['file_name'],
                'file_type' => $validatedData['file_type'],
                'file_description' => $validatedData['file_description'],
                'file_url' => $filePath,
            ]); 

            return redirect()->route('user-portfolio')->with('success', 'Project added successfully.');
        } catch (ValidationException $e) {
            // Validation failed. Redirect back with validation errors.
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            // Log the error
            $errorMessage = 'Error-save user portfolio: ' . $e->getMessage();
            Log::error($errorMessage);

            return redirect()->back()->with('error', 'An error occurred during Project update. Please try again.');
        }

    }  

    public function editUserPortfolio($id)
    {
        try {  
            $userPortfolios = UserPortfolio::findOrFail($id);
    
            return view('dashboard.edit-user-portfolio', compact('userPortfolios'));
        } catch (QueryException $e) {
            $errorMessage = 'Error-edit user portfolio: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle the exception (e.g., log it or display an error message)
            return redirect()->route('edit-user-portfolio')->with('error', 'An error occurred while retrieving user project history.');
        }
    }  

    public function updateUserPortfolio(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([               
                'file_name' => 'required|string',
                'file_type' => 'nullable|string',
                'file_description' => 'required|string',
                'file' => 'nullable|mimes:pdf,jpeg,png',
            ]);  

            // Retrieve the user education based on the $id
            $userPortfolio = UserPortfolio::findOrFail($id);

            if ($request->hasFile('file')) {
                // Handle the new certificate file
                $userFile = $request->file('file');
                $user = auth()->user();
                $username = $user->user_name; // Get the user's username

                // Generate filenames                
                $userFilename = $user->user_name .'_'. uniqid(). '_' . substr(uniqid(), 0, 5) . '.' . $userFile->getClientOriginalExtension();
                
                //Store file
                $file_type = $validatedData['file_type'];                
                if($file_type=='Image'){
                    $filePath = $userFile->storeAs('portfolio/image', $userFilename, 'public');
                } 
                elseif($file_type=='Document') {
                    $filePath = $userFile->storeAs('portfolio/document', $userFilename, 'public');
                }
                // Update the file_url field in the database
                $userPortfolio->file_url = $filePath;
                $userPortfolio->file_type = $validatedData['file_type'];
            }

            // Update other portfolio attributes based on the request data
            $userPortfolio->update([
                'file_name' => $validatedData['file_name'],
                'file_description' => $validatedData['file_description'],                
            ]);

            // Redirect to the user's education list or another appropriate page
            return redirect()->route('user-portfolio')->with('success', 'Project updated successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-update user portfolio: ' . $e->getMessage();
            Log::error($errorMessage);
            // Handle any exceptions or errors here
            return back()->with('error', 'An error occurred while updating the Project. Please try again.');
        }
    }

    public function deleteUserPortfolio($id)
    {
        try {
            $userPortfolio = UserPortfolio::findOrFail($id);
            $userPortfolio->delete();

            return redirect()->route('user-portfolio')->with('success-new', 'Project deleted successfully.');
        } catch (\Exception $e) {
            $errorMessage = 'Error-delete user portfolio: ' . $e->getMessage();
            Log::error($errorMessage);
            return redirect()->route('user-portfolio')->with('error-new', 'Project.');
        }
    }
   
    
    public function textArea()
    {
        return view('textarea');
    }
    
}
