<?php

namespace Tests\App\Http\Controllers;

use TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class BooksControllerTest extends TestCase
{
    use DatabaseMigrations;

     /**@test**/
     public function test_index_status_code_should_be_200()
     {
        $this->visit('/books')->seeStatusCode(200);
     }

     /**@test**/
    public function test_index_should_return_a_collection_of_records()
    {
        $books = factory('App\Book', 2)->create();

        $this->get('/books');

        foreach($books as $book) {
            $this->seeJson(['title' => $book->title]);
        }
    }

    public function test_show_should_retuan_a_valid_book()
    {
        $book = factory('App\Book')->create();
        $this
            ->get("/books/{$book->id}")
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $book->id,
                'title' => $book->title,
                "description"=> $book->description,
                "author"=> $book->author
            ]);

            $data = json_decode($this->response->getContent(), true);
            $this->assertArrayHasKey('created_at', $data);
            $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_show_should_fail_when_the_book_id_does_not_exist()
    {
        $this
            ->get('/books/99999')
            ->seeStatusCode(404)
            ->seeJson([
                'error' => [
                    'message' => 'Book not found'
                ]
            ]);
    }

    public function test_show_route_should_not_match_an_invalid_route()
    {
        $this->get('/books/this-is-invalid');

        $this->assertNotRegExp(
            '/Book not found/',
            $this->response->getContent(),
            'BooksController@show route matching when it should not.'
        );
    }

    public function test_store_should_save_new_book_in_the_database()
    {
        $this->post('/books', [
            'title' => 'The Invisible Man',
            'description' => 'An invisible man is trapped in the terror of his own creation',
            'author' => 'H. G. Wells'
        ]);
        $this->seeJson(['created' => true])
                ->seeInDatabase('books', ['title' => 'The Invisible Man']);
    }

    public function test_store_should_respond_with_a_201_and_location_header_when_successful()
    {
        $this->post('/books', [
            'title' => 'The Invisible Man',
            'description' => 'An invisible man is trapped in the terror of his own creation',
            'author' => 'H. G. Wells'
        ]);
        $this->seeStatusCode(201)
                ->seeHeaderWithRegExp('Location', '#/books/[\d]+$#');
    }

    public function test_update_should_only_change_fillable_fields()
    {
        $book = factory('App\Book')->create([
            'title' => 'The War of the Worlds',
            'description' => 'The book is way better than the movie.',
            'author' => 'Wells, H. G.'
        ]);

        $this->put("/books/{$book->id}", [
            'id' => 5,
            'title' => 'The War of the Worlds',
            'description' => 'The book is way better than the movie.',
            'author' => 'Wells, H. G.'
        ]);

        $this->seeStatusCode(200)
                ->seeJson([
                    'id' => 1,
                    'title' => 'The War of the Worlds',
                    'description' => 'The book is way better than the movie.',
                    'author' => 'Wells, H. G.'
                ])
                ->seeInDatabase('books', [
                    'title' => 'The War of the Worlds'
                ]);
    }

    public function test_update_should_fail_with_an_invalid_id()
    {
        $this->put('/books/99999999999')
                ->seeStatusCode(404)
                ->seeJsonEquals([
                    'error' => [
                        'message' => 'Book not found'
                    ]
                ]);
    }

    public function test_update_should_not_match_an_invalid_route()
    {
        $this->put('/books/this-is-invalid')
            ->seeStatusCode(404);
    }

    public function test_destroy_should_remove_a_valid_book()
    {
        $book = factory('App\Book')->create();
        $this
            ->delete("/books/{$book->id}")
            ->seeStatusCode(204)
            ->isEmpty();

        $this->notSeeInDatabase('books', ['id' => $book->id]);
    }

    public function test_destroy_should_return_a_404_with_an_invalid_id()
    {
        $this->delete('/books/99999999999')
                ->seeStatusCode(404)
                ->seeJsonEquals([
                    'error' => [
                        'message' => 'Book not found'
                    ]
                ]);
    }

    public function test_destroy_should_not_match_an_invalid_route()
    {
        $this->put('/books/this-is-invalid')
            ->seeStatusCode(404);
    }

}