<?php

namespace App\Http\Controllers\API\v1;

use Exception;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use function PHPUnit\Framework\isEmpty;
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
            return response()->json([
                // 
                'data' => $articles,
                'message' => 'List Articles',
                'status' => Response::HTTP_OK,
            ], Response::HTTP_OK);
        }
    }

    //function untuk insert data
    public function store(Request $request)
    {
        //validation untuk form yang dikirim
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
            'publish_date' => 'required'
        ]);

        //ketika validation gagal maka akan memunculkan error
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try {
            //ketika validation berhasil maka akan insert data
            Article::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'publish_date' => Carbon::create($request->input('publish_date'))->toDateString(),
            ]);

            return response()->json([
                'message' => 'Data Stored DB',
                'status' => Response::HTTP_OK,
            ], Response::HTTP_OK);
        } catch (Exception $e) {

            //jika error saat insert maka akan menampilkan pesan error
            Log::error("Error Storing Data " . $e->getMessage());

            return response()->json(
                [
                    'message' => 'failed stored db',
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
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

    //untuk update data
    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Article Not Found'
            ], Response::HTTP_NOT_FOUND);
        } else {
            //validation untuk form yang dikirim
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'content' => 'required',
                'publish_date' => 'required'
            ]);

            //ketika validation gagal maka akan memunculkan error
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            try {
                $article->update(
                    [
                        'title' => $request->input('title'),
                        'content' => $request->input('content'),
                        'publish_date' => Carbon::create($request->input('publish_date'))->toDateString(),
                    ]
                );
                return response()->json([
                    'status' => Response::HTTP_OK,
                    'message' => 'Article Updated'
                ], Response::HTTP_OK);
            } catch (Exception $e) {

                //jika error saat insert maka akan menampilkan pesan error
                Log::error("Error Update Data " . $e->getMessage());

                return response()->json(
                    [
                        'message' => 'failed update db',
                        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    ],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    public function delete($id)
    {
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
