<?php

namespace App\Http\Controllers\API\v2;

use Exception;
use Carbon\Carbon;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ArticleCollection;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    // Untuk menampilkan seluruh list dari data article
    public function index(Request $request)
    {
        $query = Article::latest('publish_date');
        $seacrh = $request->input('title');
        if ($seacrh) {
            $query = Article::where('title', 'like', '%' . $seacrh . '%');
        }
        $articles =  $query->paginate(2);

        if ($articles->isEmpty()) {
            //jika article kosong
            return response()->json([
                'message' => 'Article empty',
                'status' => Response::HTTP_NOT_FOUND,
            ], Response::HTTP_NOT_FOUND);
        } else {
            //jika article tidak kosong respone bisa langsung $article, tetapi ketika ingin menampilkan beberapa kolom maka dapat menggunakan map
            return new ArticleCollection($articles);
        }
    }
    //function untuk insert data
    // public function store(Request $request)
    // {
    //     $token = $request->bearerToken();
    //     if (!$token) {
    //         return response()->json([
    //             'message' => 'Token Not Exist, please login first',
    //             'status' => Response::HTTP_UNAUTHORIZED,
    //         ], Response::HTTP_UNAUTHORIZED);
    //     }

    //     //validation untuk form yang dikirim
    //     $validator = Validator::make($request->all(), [
    //         'title' => 'required',
    //         'content' => 'required',
    //         'publish_date' => 'required'
    //     ]);

    //     //ketika validation gagal maka akan memunculkan error
    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     try {
    //         //ketika validation berhasil maka akan insert data
    //         Article::create([
    //             'title' => $request->input('title'),
    //             'content' => $request->input('content'),
    //             'publish_date' => Carbon::create($request->input('publish_date'))->toDateString(),
    //         ]);

    //         return response()->json([
    //             'message' => 'Data Stored DB',
    //             'status' => Response::HTTP_OK,
    //         ], Response::HTTP_OK);
    //     } catch (Exception $e) {

    //         //jika error saat insert maka akan menampilkan pesan error
    //         Log::error("Error Storing Data " . $e->getMessage());

    //         return response()->json(
    //             [
    //                 'message' => 'failed stored db',
    //                 'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
    //             ],
    //             Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }
    public function store(Request $request)
    {
        // Validasi token dengan middleware auth:sanctum
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized, invalid token',
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Validasi form
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
            'publish_date' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Jika validasi sukses, insert data
            Article::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'publish_date' => Carbon::create($request->input('publish_date'))->toDateString(),
            ]);

            return response()->json([
                'message' => 'Data Stored in DB',
                'status' => Response::HTTP_OK,
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error("Error Storing Data " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to store in DB',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    //untuk show data bedasarkan id
    public function show($id)
    {
        $article = Article::find($id);

        if ($article) {
            return response()->json(
                [
                    'status' => Response::HTTP_OK,
                    'data' => [
                        'id' => $article->id,
                        'content' => $article->content,
                        'title' => $article->title,
                        'publish_date' => $article->publish_date,
                    ]
                ],
                Response::HTTP_OK
            );
        } else {
            return response()->json(
                [
                    'message' => 'Article Not Found',
                    'status' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function update(Request $request, $id)
    {
        // Mendapatkan token dari request
        $token = $request->bearerToken();

        // Periksa apakah token ada
        if (!$token) {
            return response()->json([
                'message' => "Can't update article. Token Not Exist, please login first",
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verifikasi token dan mendapatkan user terkait
        $user = Auth::guard('sanctum')->user(); // Ganti 'sanctum' dengan guard yang sesuai jika menggunakan selain Sanctum

        // Jika token tidak valid atau user tidak ditemukan
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized, invalid token or token expired',
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Dapatkan artikel berdasarkan ID
        $article = Article::find($id);

        // Jika artikel tidak ditemukan
        if (!$article) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Article Not Found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Validasi form yang dikirim
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required',
        ]);

        // Ketika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Update artikel
            $article->update([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'publish_date' => Carbon::parse($request->input('publish_date'))->toDateString(),
            ]);

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Article Updated Successfully',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            // Jika ada error pada proses update
            Log::error("Error updating article: " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update article, please try again later.',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete(Request $request, $id)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'message' => "Can't delete article. Token Not Exist, please login first",
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }
        $article = Article::find($id);
        if (!$article) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Article Not Found'
            ], Response::HTTP_NOT_FOUND);
        } else {
            try {

                $article->delete();
                return response()->json([
                    'status' => Response::HTTP_OK,
                    'message' => 'Article Deleted'
                ], Response::HTTP_OK);
            } catch (Exception $e) {
                Log::error("Error Delete Data " . $e->getMessage());
                return response()->json(
                    [
                        'message' => 'failed delete db',
                        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }
}
