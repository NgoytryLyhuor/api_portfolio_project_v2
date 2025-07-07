<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProjectController extends Controller
{

    public function index()
    {
        try {
            $projects = Project::all();

            // Decode technologies JSON for each project
            $projects->transform(function ($project) {
                if ($project->technologies) {
                    $project->technologies = json_decode($project->technologies, true);
                }
                return $project;
            });

            return response()->json([
                'status' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $project = Project::findOrFail($id);

            // Decode technologies JSON
            if ($project->technologies) {
                $project->technologies = json_decode($project->technologies, true);
            }

            return response()->json([
                'status' => true,
                'message' => 'Project retrieved successfully',
                'data' => $project
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'technologies' => 'required|array',
            'category' => 'required|string|max:255',
            'status' => 'required|string',
            'demo_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'show_github' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Initialize data array with validated data
        $data = $validator->validated();

        // Handle image upload
        if ($request->has('image') && $request->image) {
            $imageData = $request->image;

            // Check if it's a base64 image
            if (strpos($imageData, 'data:image/') === 0) {
                // Extract the base64 part
                $image = str_replace('data:image/', '', $imageData);
                $image = explode(';base64,', $image);
                $imageType = $image[0]; // png, jpg, etc.
                $imageBase64 = base64_decode($image[1]);

                // Generate unique filename
                $imageName = time() . '_' . Str::random(10) . '.' . $imageType;

                // Save to public/images directory
                $imagePath = public_path('images/');
                if (!file_exists($imagePath)) {
                    mkdir($imagePath, 0755, true);
                }

                file_put_contents($imagePath . $imageName, $imageBase64);
                $data['image'] = url('images/' . $imageName);
            } else {
                // Handle regular file upload
                $imagePath = $request->file('image')->store('images', 'public');
                $data['image'] = url('storage/' . $imagePath);
            }
        }

        // Convert technologies array to JSON if needed
        if (isset($data['technologies']) && is_array($data['technologies'])) {
            $data['technologies'] = json_encode($data['technologies']);
        }

        // Create the project with the prepared data
        $project = Project::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $project = Project::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'image' => 'nullable|string',
                'technologies' => 'sometimes|required|array',
                'category' => 'sometimes|required|string|max:255',
                'status' => 'sometimes|required|string',
                'demo_url' => 'nullable|url',
                'github_url' => 'nullable|url',
                'show_github' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Handle image upload/update
            if ($request->has('image') && $request->image) {
                // Delete old image if exists
                if ($project->image) {
                    $oldImagePath = str_replace(url('/'), '', $project->image);
                    $fullOldImagePath = public_path($oldImagePath);
                    if (File::exists($fullOldImagePath)) {
                        File::delete($fullOldImagePath);
                    }
                }

                $imageData = $request->image;

                // Check if it's a base64 image
                if (strpos($imageData, 'data:image/') === 0) {
                    // Extract the base64 part
                    $image = str_replace('data:image/', '', $imageData);
                    $image = explode(';base64,', $image);
                    $imageType = $image[0]; // png, jpg, etc.
                    $imageBase64 = base64_decode($image[1]);

                    // Generate unique filename
                    $imageName = time() . '_' . Str::random(10) . '.' . $imageType;

                    // Save to public/images directory
                    $imagePath = public_path('images/');
                    if (!file_exists($imagePath)) {
                        mkdir($imagePath, 0755, true);
                    }

                    file_put_contents($imagePath . $imageName, $imageBase64);
                    $data['image'] = url('images/' . $imageName);
                } else {
                    // Handle regular file upload
                    $imagePath = $request->file('image')->store('images', 'public');
                    $data['image'] = url('storage/' . $imagePath);
                }
            } elseif ($request->has('image') && $request->image === null) {
                // If image is explicitly set to null, remove the old image
                if ($project->image) {
                    $oldImagePath = str_replace(url('/'), '', $project->image);
                    $fullOldImagePath = public_path($oldImagePath);
                    if (File::exists($fullOldImagePath)) {
                        File::delete($fullOldImagePath);
                    }
                }
                $data['image'] = null;
            }

            // Convert technologies array to JSON if needed
            if (isset($data['technologies']) && is_array($data['technologies'])) {
                $data['technologies'] = json_encode($data['technologies']);
            }

            // Update the project
            $project->update($data);

            // Decode technologies for response
            $project->fresh();
            if ($project->technologies) {
                $project->technologies = json_decode($project->technologies, true);
            }

            return response()->json([
                'status' => true,
                'message' => 'Project updated successfully',
                'data' => $project
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);

            // Delete associated image if exists
            if ($project->image) {
                $imagePath = str_replace(url('/'), '', $project->image);
                $fullImagePath = public_path($imagePath);
                if (File::exists($fullImagePath)) {
                    File::delete($fullImagePath);
                }
            }

            // Delete the project
            $project->delete();

            return response()->json([
                'status' => true,
                'message' => 'Project deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Project not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByCategory($category)
    {
        try {
            $projects = Project::where('category', $category)->get();

            // Decode technologies JSON for each project
            $projects->transform(function ($project) {
                if ($project->technologies) {
                    $project->technologies = json_decode($project->technologies, true);
                }
                return $project;
            });

            return response()->json([
                'status' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByStatus($status)
    {
        try {
            $projects = Project::where('status', $status)->get();

            // Decode technologies JSON for each project
            $projects->transform(function ($project) {
                if ($project->technologies) {
                    $project->technologies = json_decode($project->technologies, true);
                }
                return $project;
            });

            return response()->json([
                'status' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projects
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
