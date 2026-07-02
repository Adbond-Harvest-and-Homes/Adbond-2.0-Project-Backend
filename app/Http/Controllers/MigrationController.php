<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

use app\Models\Client; 
use app\Models\User;
use app\Models\File;
use app\Models\Country;
use app\Models\Assessment;
use app\Models\AssessmentAttempt;
use app\Models\Question;
use app\Models\QuestionOption;
use app\Models\AssessmentAttemptAnswer;
use app\Models\Bank;
use app\Models\BankAccount;
use app\Models\Post;
use app\Models\Comment;
use app\Models\Reaction;
use app\Models\ClientNextOfKin;
use app\Models\Project;
use app\Models\Package;
use app\Models\PackageMedia;
use app\Models\ClientPackage;
use app\Models\Offer;
use app\Models\OfferBid;
use app\Models\Order;
use app\Models\Payment;
use app\Models\PaymentStatus;
use app\Models\PaymentMode;
use app\Models\OrderDiscount;
use app\Models\SiteTourSchedule;
use app\Models\SiteTourBooking;
use app\Models\SiteTourBookedSchedule;
use app\Models\StaffCommissionEarning;
use app\Models\StaffCommissionRedemption;
use app\Models\StaffCommissionTransaction;
use app\Models\Promo;
use app\Models\PromoProduct;

use app\Models\TableMigration;

use app\Enums\FilePurpose;
use app\Enums\PostType;
use app\Enums\ProductCategory;
use app\Enums\PackageType;
use app\Enums\OrderType;
use app\Enums\PaymentPurpose;
use app\Enums\ClientPackageOrigin;
use app\Enums\RedemptionStatus;

use app\Services\UtilityService;

use app\Utilities;

class MigrationController extends Controller
{
    private $assessmentsMigration;
    private $questionsMigration;
    private $questionOptionsMigration;
    private $staffAssessmentsMigration;
    private $staffAssessmentAnswersMigration;

    private $bankAccountsMigration;
    private $usersMigration;
    private $customersMigration;
    private $postsMigration;
    private $commentsMigration;
    private $newsMigration;
    private $reactionsMigration;

    private $nextOfKinMigration;
    private $projectsMigration;
    private $projectLocationsMigration;
    private $packagesMigration;
    private $packageItemsMigration;
    private $packagePhotosMigration;
    private $customerPackageMigration;
    private $ordersMigration;
    private $paymentsMigration;
    private $offersMigration;
    private $orderDiscountsMigration;
    private $salesOfferPaymentMigration;
    private $offerBidsMigration;

    private $monthlyWeekDaysMigration;
    private $inspectionDaysMigration;
    private $inspectionRequestsMigration;
    private $userCommissionsMigration;
    private $userCommissionPaymentsMigration;

    private $promosMigration;
    private $promoProductsMigration;

    public function __construct()
    {
        // $this->assessmentsMigration = TableMigration::where("name", "assessments")->first();
        // $this->questionsMigration = TableMigration::where("name", "questions")->first();
        // $this->questionOptionsMigration = TableMigration::where("name", "question_options")->first();
        $this->staffAssessmentsMigration =  TableMigration::where("name", "virtual_staff_assessments")->first();
        $this->staffAssessmentAnswersMigration =  TableMigration::where("name", "virtual_staff_assessment_answers")->first();

        // $this->bankAccountsMigration = TableMigration::where("name", "bank_accounts")->first();
        // $this->usersMigration = TableMigration::where("name", "users")->first();
        $this->customersMigration = TableMigration::where("name", "customers")->first();
        $this->postsMigration = TableMigration::where("name", "posts")->first();
        // $this->commentsMigration = TableMigration::where("name", "comments")->first();
        $this->newsMigration = TableMigration::where("name", "news")->first();
        // $this->reactionsMigration = TableMigration::where("name", "reactions")->first();

        $this->nextOfKinMigration = TableMigration::where("name", "customer_next_of_kins")->first();
        // $this->projectLocationsMigration = TableMigration::where("name", "project_locations")->first();
        $this->projectsMigration = TableMigration::where("name", "projects")->first();
        // $this->packagesMigration = TableMigration::where("name", "packages")->first();
        // $this->packageItemsMigration = TableMigration::where("name", "package_items")->first();
        // $this->packagePhotosMigration = TableMigration::where("name", "package_photos")->first();
        $this->customerPackageMigration = TableMigration::where("name", "customer_packages")->first();
        $this->ordersMigration = TableMigration::where("name", "orders")->first();
        $this->orderDiscountsMigration = TableMigration::where("name", "order_discounts")->first();
        $this->paymentsMigration = TableMigration::where("name", "payments")->first();
        $this->offersMigration = TableMigration::where("name", "offers")->first();
        // $this->salesOfferPaymentMigration = TableMigration::where("name", "sales_offer_payments")->first();
        // $this->offerBidsMigration = TableMigration::where("name", "offer_bids")->first();

        // $this->monthlyWeekDaysMigration = TableMigration::where("name", "monthly_week_days")->first();
        // $this->inspectionDaysMigration = TableMigration::where("name", "inspection_days")->first();
        // $this->inspectionRequestsMigration = TableMigration::where("name", "inspection_requests")->first();
        $this->userCommissionsMigration = TableMigration::where("name", "user_commissions")->first();
        $this->userCommissionPaymentsMigration = TableMigration::where("name", "user_commission_payments")->first();

        // $this->promosMigration = TableMigration::where("name", "promos")->first();
        // $this->promoProductsMigration = TableMigration::where("name", "promo_products")->first();

        /*
            orders - 2025-10-01
            Staff_assessments = 2025-09-29
            Staff_assessment_answerss = 2025-09-29
            Customers = 2025-10-02
            posts = 2025-09-10
            news = 2025-08-29
            nextOfKin = 2025-10-01
            customer_packages = 2025-10-01
            orders = 2025-10-01
            order_discounts = 2025-09-30
            payments = 2025-10-02
            offers = 2025-10-01
            user_commissions = 2025-10-01
            user_commission_payments = 2025-10-01

        */
    }
    
    public function index()
    {
        try{
            // if(!$this->assessmentsMigration->migrated) $this->assessments();
            // if(!$this->questionsMigration->migrated) $this->questions();
            // if(!$this->questionOptionsMigration->migrated) $this->questionOptions();
            // if(!$this->staffAssessmentsMigration->migrated) $this->assessmentAttempts();

            // if(!$this->usersMigration->migrated) $this->users();
            // if(!$this->customersMigration->migrated) $this->clients();

            // if(!$this->bankAccountsMigration->migrated) $this->bankAccounts();
            // if(!$this->postsMigration->migrated) $this->posts();
            // if(!$this->commentsMigration->migrated) $this->comments();
            // if(!$this->newsMigration->migrated) $this->news();
            // if(!$this->reactionsMigration->migrated) $this->reactions();

            if(!$this->projectsMigration->migrated) $this->projects();
            
            // if(!$this->nextOfKinMigration->migrated) $this->nextOfKins();
            // if(!$this->inspectionDaysMigration->migrated) $this->siteTours();
            // if(!$this->userCommissionsMigration->migrated) $this->userCommissions();
            
            // if(!$this->promosMigration->migrated) $this->promos();

        }catch(\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    private function markAsMigrated($migration) {
        $migration->migrated = true;
        $migration->update();
    }

    public function clients()
    {
        $sourceTable = 'customers'; // table in v1

        try{
            DB::beginTransaction();
            // Fetch from v1 in chunks (to handle large data)
            DB::connection('db1')->table($sourceTable)->where("created_at", ">", "2025-10-02")->orderBy('id')->chunk(500, function ($records) {
                foreach ($records as $record) {
                    // Convert to array
                    $data = (array) $record;

                    $countryId = null;
                    if($data['country_id']) {
                        $countryRecord = DB::connection('db1')->table('countries')->where('id', $data['country_id'])->first();
                        $countryRecord = (array) $countryRecord;
                        if($countryRecord) {
                            $country = Country::where("name", $countryRecord['name'])->first();
                            if($country) $countryId = $country->id;
                        }
                    }

                    // Use Eloquent model for v2
                    $client = new Client;
                    $client->title = $data['title'];
                    $client->firstname = $data['firstname'];
                    $client->lastname = $data['lastname'];
                    // $client->othernames
                    $client->email = $data['email'];
                    $client->password = $data['password'];
                    $client->phone_number = $data['phone_number'];
                    // $client->photo_id 
                    // $client->client_identification_id
                    $client->dob = $data['dob'];
                    $client->gender = $data['gender'];
                    $client->provider_id = $data['provider_id'];
                    $client->provider_name = $data['provider_name'];
                    $client->email_verified_at = $data['email_verified_at'];
                    $client->country_id = $countryId;
                    // $client->state_id 
                    $client->age_group_id = $data['age_group_id'];
                    $client->address = $data['address'];
                    $client->postal_code = $data['postal_code'];
                    $client->marital_status = $data['marital_status'];
                    $client->employment_status = $data['employment_status'];
                    $client->occupation = $data['occupation'];
                    $client->activated = $data['activated'];
                    // $client->kyc_status = 
                    $client->referer_id = $data['referer_id'];
                    if($data['referer_id']) $client->referer_type = User::$userType;
                    // $client->referer_code
                    $client->migrated = true;

                    $client->save();

                    $photo = null;

                    if($data['photo_id']) {
                        $photoRecord = DB::connection('db1')->table('files')->where('id', $data['photo_id'])->first();
                        $photoRecord = (array) $photoRecord;
                        if($photoRecord) {
                            $user = ["id" => $client->id, "type" => Client::$userType];
                            $file = $this->migrateFile($photoRecord, $user, $user, FilePurpose::CLIENT_PROFILE_PHOTO);
                            if($file) {
                                $client->photo_id = $file->id;
                                $client->update();
                            }
                        }
                    }

                    // User::updateOrCreate(
                    //     ['id' => $data['id']], // match by id
                    //     $data                  // fill attributes
                    // );
                }
            });
            $this->markAsMigrated($this->customersMigration);
            DB::commit();
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }

        // return response()->json(['message' => 'Clients copied successfully!']);
    }

    public function assessments()
    {
        // dd('dont get here');
        $sourceTable = 'assessments'; // table in v1
        try{
            DB::beginTransaction();
            // Fetch from v1 in chunks (to handle large data)
            DB::connection('db1')->table($sourceTable)->orderBy('id')->chunk(500, function ($records) {
                foreach ($records as $record) {
                    // Convert to array
                    $data = (array) $record;

                    // Use Eloquent model for v2
                    $assessment = new Assessment;
                    $assessment->title = $data['title'];
                    $assessment->description = $data['description'];
                    $assessment->active = $data['active'];
                    $assessment->created_at = $data['created_at'];
                    $assessment->updated_at = $data['updated_at'];
                    $assessment->migrated = true;

                    $assessment->save();
                }
            });

            $this->markAsMigrated($this->assessmentsMigration);
            DB::commit();
            // return response()->json(['message' => 'Assessments copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function questions()
    {
        // dd('dont get here');
        // Fetch from v1 in chunks (to handle large data)
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('questions')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $assessment = Assessment::where("migrated", true)->first();

                        if($assessment) {
                            // Convert to array
                            $data = (array) $record;

                            // Use Eloquent model for v2
                            $question = new Question;
                            $question->assessment_id = $assessment->id;
                            $question->question = $data['question'];
                            $question->migrated = true;

                            $question->save();
                        }
                    }
                }
            });
            $this->markAsMigrated($this->questionsMigration);
            DB::commit();
            // return response()->json(['message' => 'Assessment Questions copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }

    }

    public function questionOptions()
    {
        // dd('dont get here');
        // Fetch from v1 in chunks (to handle large data)
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('question_options')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $v1Question = DB::connection('db1')->table('questions')->where("id", $data['question_id'])->first();
                        if($v1Question) {
                            $question = Question::where("question", $v1Question->question)->first();

                            if($question) {
                                // Use Eloquent model for v2
                                $option = new QuestionOption;
                                $option->question_id = $question->id;
                                $option->value = $data['value'];
                                $option->answer = $data['answer'];
                                $option->migrated = true;

                                $option->save();
                            }
                        }
                    }
                }
            });
            $this->markAsMigrated($this->questionOptionsMigration);
            DB::commit();
            // return response()->json(['message' => 'Assessment Questions copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function assessmentAttempts()
    {
        // Fetch from v1 in chunks (to handle large data)
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('virtual_staff_assessments')->where("created_at", ">", "2025-09-29")->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $assessment = Assessment::where("migrated", true)->first();

                        if($assessment) {
                            // Convert to array
                            $data = (array) $record;

                            // Use Eloquent model for v2
                            $attempt = new AssessmentAttempt;
                            $attempt->assessment_id = $assessment->id;
                            $attempt->firstname = $data['firstname'];
                            $attempt->surname = $data['lastname'];
                            $attempt->email = $data['email'];
                            $attempt->phone_number = $data['phone_number'];
                            $attempt->address = $data['address'];
                            $attempt->gender = $data['gender'];
                            $attempt->occupation = $data['occupation'];
                            $attempt->referral_code = $data['referal_code'];
                            $attempt->score = $data['score'];
                            // $attempt->cut_off_mark = $data['cut_off_mark'];
                            $attempt->passed = $data['passed'];
                            // $attempt->correct = $data['correct'];
                            // $attempt->total_questions = $data['total_questions'];
                            // $attempt->started_at = $data['started_at'];
                            // $attempt->time_used = $data['time_used'];
                            $attempt->cancelled = ($data['status'] == -1) ? 1 : 0;
                            // $attempt->disqualified = $data['disqualified'];
                            $attempt->created_at = $data['created_at'];
                            $attempt->updated_at = $data['updated_at'];
                            $attempt->migrated = true;

                            $attempt->save();

                            $this->attemptAnswers($data['id'], $attempt->id);
                        }
                    }
                }
            });
            $this->markAsMigrated($this->staffAssessmentsMigration);
            DB::commit();
            // return response()->json(['message' => 'Assessment Attempts copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function attemptAnswers($staffAssessmentId, $attemptId)
    {
        try{
            DB::beginTransaction();
            // Fetch from v1 in chunks (to handle large data)
            DB::connection('db1')->table('virtual_staff_assessment_answers')->where("virtual_staff_assessment_id", $staffAssessmentId)->where("created_at", ">", "2025-09-29")->orderBy('id')->chunk(500, function ($records) use($attemptId) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $question = Question::where("question", $data['question'])->first();

                        if($question) {

                            $attemptAnswer = new AssessmentAttemptAnswer;

                            $attemptAnswer->attempt_id = $attemptId;
                            $attemptAnswer->question = $data['question'];
                            $attemptAnswer->question_id = $question->id;
                            $attemptAnswer->answer = $data['answer'];
                            $attemptAnswer->correct_answer = $data['correct_answer'];
                            $attemptAnswer->correct = $data['correct'];
                            $attemptAnswer->created_at = $data['created_at'];
                            $attemptAnswer->updated_at = $data['updated_at'];
                            $attemptAnswer->migrated = true;

                            $attemptAnswer->save();
                        }
                    }
                }
            });
            $this->markAsMigrated($this->staffAssessmentAnswersMigration);
            DB::commit();
            // return response()->json(['message' => 'Assessment Attempt Answers copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function bankAccounts()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('bank_accounts')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $bank = Bank::where("name", "LIKE", "%".$data['bank']."%")->first();
                        if($bank) {
                            // Use Eloquent model for v2
                            $account = new BankAccount;
                            $account->number = $data['account_number'];
                            $account->name = $data['account_name'];
                            $account->bank_id = $bank->id;
                            $account->active = $data['active'];
                            $account->migrated = true;

                            $account->save();
                        }
                    }
                }
            });
            $this->markAsMigrated($this->bankAccountsMigration);
            DB::commit();
            // return response()->json(['message' => 'Bank Accounts copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function users()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('users')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;
                        
                        // Use Eloquent model for v2
                        $existingUser = User::where("email", $data['email'])->first();
                        if($existingUser) $existingUser->delete();
                        $user = new User;
                        // $user->title = $data['account_number'];
                        $user->firstname = $data['firstname'];
                        $user->lastname = $data['lastname'];
                        $user->email = $data['email'];
                        $user->email_verified_at = $data['email_verified_at'];
                        $user->email_confirmed = $data['email_confirmed'];
                        $user->password = $data['password'];
                        $user->password_set = 1;
                        $user->role_id = $data['role_id'];
                        $user->staff_type_id = $data['staff_type_id'];
                        $user->phone_number = $data['phone_number'];
                        $user->address = $data['address'];
                        $user->postal_code = $data['postal_code'];
                        $user->marital_status = $data['marital_status'];
                        $user->gender = $data['gender'];
                        $user->photo_id = $data['photo_id'];
                        $user->referer_code = $data['referer_code'];
                        $user->activated = $data['activated'];
                        $user->registered_by = $data['registered_by'];
                        $user->commission = $data['commission'];
                        $user->commission_balance = $data['commission_balance'];
                        $user->commission_before_tax = $data['commission_before_tax'];
                        $user->hybrid_staff_draw_id = $data['hybrid_staff_draw_id'];
                        $user->account_number = $data['account_number'];
                        $user->account_name = $data['account_name'];
                        $user->bank_id = $data['bank_id'];
                        $user->date_joined = $this->isValidDate($data['date_joined']) ? $data['date_joined'] : $this->extractDate($data['created_at']);
                        $user->created_at = $data['created_at'];
                        $user->updated_at = $data['updated_at'];

                        $user->migrated = true;

                        $user->save();
                    }
                }
            });
            $this->markAsMigrated($this->usersMigration);
            DB::commit();
            // return response()->json(['message' => 'Users copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function posts()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('posts')->where("created_at", ">", "2025-09-10")->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $coverPhoto = DB::connection('db1')->table('files')->where("id", $data['cover_photo_id'])->first();
                        $db1User = DB::connection('db1')->table('users')->where("id", $data['user_id'])->first();
                        
                        $user = null;
                        $file = null;
                        if($db1User) {
                            $db1User = (array) $db1User;
                            $user = User::where("email", $db1User['email'])->first();
                        }
                        // dd($coverPhoto && $user);
                        if($coverPhoto && $user) {
                            $post = new Post;
                            $post->post_type = PostType::BLOG->value;
                            $post->file_id = $coverPhoto->id;
                            $post->topic = $data['title'];
                            $post->slug = $data['slug'];
                            $post->preview = $this->getPreview($data['description']);
                            $post->content = $data['body'];
                            $post->active = $data['published'];
                            $post->user_id = $user->id;
                            $post->created_at = $data['created_at'];
                            $post->updated_at = $data['updated_at'];
                            $post->migrated = true;

                            $post->save();

                            $coverPhoto = (array) $coverPhoto;
                            $file = $this->migrateFile($coverPhoto, ["id"=>$user->id, "type"=>User::$userType], ['id'=>$post->id, 'type'=>Post::$type], FilePurpose::POST_MEDIA->value);
                            if($file) {
                                $post->file_id = $file->id;
                                $post->update();
                            }
                        }
                    }
                }
            });
            $this->markAsMigrated($this->postsMigration);
            DB::commit();
            // return response()->json(['message' => 'Posts copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function comments()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('comments')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $db1Post = DB::connection('db1')->table('posts')->where("id", $data['post_id'])->first();
                        $user = null;
                        $post = null;
                        if($data['user_type'] == User::$userType) {
                            $db1User = DB::connection('db1')->table('users')->where("id", $data['user_id'])->first();
                        }else{
                            $db1User = DB::connection('db1')->table('customers')->where("id", $data['user_id'])->first();
                        }

                        if($db1Post) $post = Post::where("topic", $data['title'])->first();
                        
                        if($db1User) {
                            $db1User = (array) $db1User;
                            $userClass = ($data['user_type'] == User::$userType) ? User::class : Client::class;
                            $user = $userClass::where("email", $db1User['email'])->first();
                        }
                        if($post && $user) {
                            $comment = new Comment;
                            $comment->post_id = $post->id;
                            $comment->message = $data['body'];
                            $comment->commenter_type = $data['user_type'];
                            $comment->commenter_id = $user->id;
                            $comment->created_at = $data['created_at'];
                            $comment->updated_at = $data['updated_at'];
                            $comment->migrated = true;

                            $comment->save();
                        }
                    }
                }
            });
            $this->markAsMigrated($this->commentsMigration);
            DB::commit();
            // return response()->json(['message' => 'comments copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function news()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('news')->where("created_at", ">", "2025-08-29")->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $coverPhoto = DB::connection('db1')->table('files')->where("id", $data['cover_photo_id'])->first();
                        $db1User = DB::connection('db1')->table('users')->where("id", $data['user_id'])->first();
                        
                        $user = null;
                        $file = null;
                        if($db1User) {
                            $db1User = (array) $db1User;
                            $user = User::where("email", $db1User['email'])->first();
                        }
                        if($coverPhoto && $user) {
                            $post = new Post;
                            $post->post_type = PostType::NEWS->value;
                            $post->file_id = $coverPhoto->id;
                            $post->topic = $data['title'];
                            $post->slug = $data['slug'];
                            $post->preview = $this->getPreview($data['description']);
                            $post->content = $data['description'];
                            $post->active = 1;
                            $post->user_id = $user->id;
                            $post->views = $data['views'];
                            $post->created_at = $data['created_at'];
                            $post->updated_at = $data['updated_at'];
                            $post->migrated = true;

                            $post->save();

                            $coverPhoto = (array) $coverPhoto;
                            $file = $this->migrateFile($coverPhoto, ["id"=>$user->id, "type"=>User::$userType], ['id'=>$post->id, 'type'=>Post::$type], FilePurpose::POST_MEDIA->value);
                            if($file) {
                                $post->file_id = $file->id;
                                $post->update();
                            }
                        }
                    }
                }
            });
            $this->markAsMigrated($this->newsMigration);
            DB::commit();
            // return response()->json(['message' => 'News copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function reactions()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('reactions')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $reaction = new Reaction;
                        $reaction->user_type = $data['user_type'];
                        $reaction->user_id = $data['user_id'];
                        $reaction->reaction = $data['reaction'];
                        $reaction->entity_type = $data['entity_type'];
                        $reaction->entity_id = $data['entity_id'];
                        $reaction->migrated = true;

                        $reaction->save();
                    }
                }
            });
            $this->markAsMigrated($this->reactionsMigration);
            DB::commit();
            // return response()->json(['message' => 'Reactions copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function nextOfKins()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('customer_next_of_kins')->where("created_at", ">", "2025-10-01")->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        
                        $data = (array) $record;

                        $customer = DB::connection('db1')->table('customers')->where("id", $data['customer_id'])->first();
                        if($customer) {
                            $customer = (array) $customer;
                            $client = Client::where("email", $customer['email'])->first();
                            
                            if($client) {
                                $nextOfKin = new ClientNextOfKin;
                                $nextOfKin->client_id = $client->id;
                                $nextOfKin->title = $data['title'];
                                $nextOfKin->firstname = $data['firstname'];
                                $nextOfKin->lastname = $data['lastname'];
                                $nextOfKin->gender = $data['gender'];
                                $nextOfKin->email = $data['email'];
                                $nextOfKin->phone_number = $data['phone_number'];
                                $nextOfKin->country_id = $data['country_id'];
                                $nextOfKin->address = $data['address'];
                                $nextOfKin->relationship = $data['relationship'];
                                $nextOfKin->created_at = $data['created_at'];
                                $nextOfKin->updated_at = $data['updated_at'];
                                $nextOfKin->migrated = true;

                                $nextOfKin->save();
                            }
                        }
                    }
                }
            });
            $this->markAsMigrated($this->nextOfKinMigration);
            DB::commit();
            // return response()->json(['message' => 'Customer Next Of Kin copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    public function projects()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('projects')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $this->projectLocations($record);
                    }
                }
            });
            $this->markAsMigrated($this->projectLocationsMigration);
            $this->markAsMigrated($this->projectsMigration);
            $this->markAsMigrated($this->packagesMigration);
            $this->markAsMigrated($this->packageItemsMigration);
            $this->markAsMigrated($this->ordersMigration);
            $this->markAsMigrated($this->orderDiscountsMigration);
            $this->markAsMigrated($this->customerPackageMigration);
            $this->markAsMigrated($this->offersMigration);
            $this->markAsMigrated($this->offerBidsMigration);
            $this->markAsMigrated($this->salesOfferPaymentMigration);
            DB::commit();
            // return response()->json(['message' => 'Customer Next Of Kin copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    private function projectLocations($v1Project)
    {
        $v1Project = (array) $v1Project;
        $category = DB::connection('db1')->table('categories')->where("id", $v1Project['category_id'])->first();
        $projectLocations = DB::connection('db1')->table('project_locations')->where("project_id", $v1Project['id'])->get();
        if($category && $projectLocations->count() > 0) {
            $category = (array) $category;
            foreach($projectLocations as $projectLocation) {
                $projectLocation = (array) $projectLocation;
                $state = DB::connection('db1')->table('states')->where("id", $projectLocation['state_id'])->first();
                if($state) {
                    $state = (array) $state;
                    $project = null; // new Project;
                    if($projectLocations->count() > 1) {
                        // $project->name = $v1Project['name']." ".$state['name'];
                        $project = Project::where("name", $v1Project['name']." ".$state['name'])->first();
                    }else{
                        // $project->name = $v1Project['name'];
                        $project = Project::where("name", $v1Project['name'])->first();
                    }
                    // $project->project_type_id = $v1Project['category_id'];
                    // $project->description = $v1Project['description'];
                    // $project->active = $projectLocation['active'];
                    // $project->state = $state['name'];
                    // $project->deactivated_at = $projectLocation['deactivated_at'];
                    // $project->created_at = $projectLocation['created_at'];
                    // $project->updated_at = $projectLocation['updated_at'];
                    // $project->migrated = true;
                    // $project->save();

                    $typeCode = strtoupper(substr($category['name'], 0, 3));
                    $idCode = str_pad($project->id, 3, '0', STR_PAD_LEFT);
                    // $project->identifier = "ADB".$typeCode.'-'.$idCode;
                    // $project->update();

                    // migrate project packages
                    if($project) $this->migratePackages($projectLocation, $project);

                    // Utilities::logSuccessMigration("Project Migration Successful.. ProjectId: ".$project->id);
                }else{
                    Utilities::logFailedMigration("Project not Migrated, State not found.. ProjectLocationId: ".$projectLocation['id']);
                }
                
            }
        }else{
            Utilities::logFailedMigration("Project not Migrated, Category not found or no project location");
        }
    }

    private function migratePackages($v1ProjectLocation, $project)
    {
        DB::connection('db1')->table('packages')->where("project_location_id", $v1ProjectLocation['id'])->orderBy('id')->chunk(500, function ($records) use($project, $v1ProjectLocation) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Package = (array) $record;
                    DB::connection('db1')->table('package_items')->where("package_id", $v1Package['id'])->orderBy('id')->chunk(500, function ($itemRecords) use($v1ProjectLocation, $project, $v1Package) {
                        if(count($itemRecords) > 0) {
                            foreach($itemRecords as $itemRecord) {
                                $packageItem = (array) $itemRecord;
                                $user = $this->getUser($v1Package['user_id']);
                                if(!$user) $this->getUser(1);

                                if($v1Package['package_brochure_file_id']) {
                                    $brochure = DB::connection('db1')->table('files')->where("id", $v1Package['package_brochure_file_id'])->first();
                                    $brochure = (array) $brochure;
                                }else{
                                    $brochure = null;
                                }
                                
                                if($user) {         
                                    $package = null; // new Package;
                                    // $package->user_id = $user->id;
                                    // $package->name = (count($itemRecords) > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $itemRecords) : $v1Package['name'];
                                    $name = (count($itemRecords) > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $itemRecords) : $v1Package['name'];
                                    $package = Package::where("name", $name)->where("category", ProductCategory::PURCHASE->value)->first();
                                    // $package->category = ProductCategory::PURCHASE->value;
                                    // $package->state = $project->state;
                                    // $package->address = $v1ProjectLocation['address'];
                                    // $package->project_id = $project->id;
                                    // $package->size = $packageItem['size'];
                                    // $package->amount = $packageItem['price'];
                                    // $package->units = $packageItem['available_units'];
                                    // $package->available_units = $packageItem['available_units'];
                                    // $package->discount = $packageItem['discount'];
                                    // $package->min_price = $packageItem['min_price'];
                                    // $package->installment_duration = $packageItem['installment_duration'];
                                    // $package->infrastructure_fee = $packageItem['infrastructure_fee'];
                                    // $package->description = $v1Package['description'];
                                    // $package->type = PackageType::NON_INVESTMENT->value;
                                    // $package->installment_option = 1;
                                    // $package->active = $v1Package['active'];
                                    // $package->deactivated_at = $v1Package['deactivated_at'];
                                    // $package->sold_out = ($packageItem['available_units'] <= 0) ? 1 : 0;
                                    // $package->created_at = $v1Package['created_at'];
                                    // $package->updated_at = $v1Package['updated_at'];
                                    // $package->migrated = true;
                                    // $package->save();

                                    // $brochureFile = ($brochure) ? $this->migrateFile($brochure, ['id' => $user->id, 'type' => User::$userType], ['id'=>$package->id, 'type'=>Package::$type], FilePurpose::PACKAGE_BROCHURE) : null;
                                    // if($brochureFile) {
                                    //     $package->package_brochure_file_id = $brochureFile->id;
                                    //     $package->update();
                                    // }

                                    // Utilities::logSuccessMigration("Package Migration Successful.. PackageId: ".$package->id);

                                    //Migrate Package Orders
                                    if($package) $this->migrateOrders($packageItem, $package);

                                    //Migrate Package Photos
                                    // $this->migratePackagePhotos($v1Package, $package, $user);
                                }else{
                                    Utilities::logFailedMigration("Package not Migrated.. User not found V1PackageId: ".$v1Package['id']);
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    private function migrateOrders($packageItem, $package)
    {
        DB::connection('db1')->table('orders')->where("package_item_id", $packageItem['id'])->where("created_at", ">", "2025-10-01")->orderBy('id')->chunk(500, function ($records) use($packageItem, $package) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Order = (array) $record;
                    $client = $this->getClient($v1Order['customer_id']);

                    $v1PaymentStatus = DB::connection('db1')->table('payment_statuses')->where("id", $v1Order['payment_status_id'])->first();
                    $paymentStatus = null;
                    if($v1PaymentStatus) {
                        $v1PaymentStatus = (array) $v1PaymentStatus;
                        $paymentStatus = PaymentStatus::where("name", "LIKE",  "%".$v1PaymentStatus['name']."%")->first();
                    }
                    if(!$v1PaymentStatus) $paymentStatus = PaymentStatus::pending();
                    // dd($paymentStatus?->id);

                    if($client) {
                        $order = new Order;
                        $order->type = OrderType::PURCHASE->value;
                        $order->client_id = $client->id;
                        $order->package_id = $package->id;
                        $order->units = $v1Order['units'];
                        $order->amount_payed = $v1Order['amount_payed'];
                        $order->amount_payable = $v1Order['amount_payable'];
                        $order->unit_price = $package->amount;
                        $order->is_installment = $v1Order['installment'];
                        // $order->installment_count = ;
                        // $order->installments_payed = ;
                        $order->balance = $v1Order['balance'];
                        $order->payment_status_id = $paymentStatus?->id;
                        $order->completed = ($v1Order['balance'] <= 0);
                        $order->order_date = $v1Order['order_date'];
                        $order->payment_due_date = $v1Order['payment_due_date'];
                        $order->grace_period_end_date = $v1Order['grace_period_end_date'];
                        $order->penalty_period_end_date = $v1Order['penalty_period_end_date'];
                        $order->payment_period_status_id = $v1Order['payment_period_status_id'];
                        $order->created_at = $v1Order['created_at'];
                        $order->updated_at = $v1Order['updated_at'];
                        $order->migrated = true;
                        $order->save();

                        $processingId = Utilities::getOrderProcessingId();
                        $order->order_number = $order->id.$processingId;
                        $order->update();

                        Utilities::logSuccessMigration("Order Migration Successful.. OrderId: ".$order->id);

                        $this->migrateOrderDiscounts($v1Order, $order);
                        $this->migratePayments($v1Order, $order);
                        $this->migrateOrderClientPackages($v1Order, $order);
                    }else{
                        Utilities::logFailedMigration("Order not Migrated.. Client not found V1OrderId: ".$v1Order['id']);
                    }
                }
            }
        });
    }

    private function migrateOrderDiscounts($v1Order, $order)
    {
        $v1Discounts = DB::connection('db1')->table('order_discounts')->where('order_id', $v1Order['id'])->get();
        if($v1Discounts->count() > 0) {
            foreach($v1Discounts as $v1Discount) {
                $v1Discount = (array) $v1Discount;
                $discount = new OrderDiscount;
                $discount->order_id = $order->id;
                $discount->type = $v1Discount['type'];
                $discount->discount = $v1Discount['discount'];
                $discount->amount = Utilities::getDiscount($order->amount_payable, $v1Discount['discount'])['amount'];
                $discount->description = $v1Discount['description'];
                $discount->migrated = true;
                $discount->created_at = $v1Discount['created_at'];
                $discount->updated_at = $v1Discount['updated_at'];
                $discount->save();

                Utilities::logSuccessMigration("Order Discount Migration Successful.. OrderDiscountId: ".$discount->id);
            }
        }
    }

    private function migratePayments($v1Order, $order)
    {
        DB::connection('db1')->table('payments')->where("order_id", $v1Order['id'])->orderBy('id')->chunk(500, function ($records) use($v1Order, $order) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Payment = (array) $record;
                    $paymentMode = DB::connection('db1')->table('payment_modes')->where("id", $v1Payment['payment_mode_id'])->first();
                    if($paymentMode) {
                        $paymentMode = (array) $paymentMode;
                        $paymentMode = PaymentMode::where('name', 'LIKE', '%'.$paymentMode['name'].'%')->first();
                    }
                    $bankAccount = ($v1Payment['bank_account_id']) ? $this->getBankAccount($v1Payment['bank_account_id']) : null;
                    $user = ($v1Payment['user_id']) ? $this->getUser($v1Payment['user_id']) : null;
                    $client = $this->getClient($v1Order['customer_id']);

                    if($client) {
                        $payment = new Payment;
                        $payment->client_id = $client->id;
                        $payment->purchase_id = $order->id;
                        $payment->purchase_type = Order::$type;
                        $payment->receipt_no = $v1Payment['receipt_no'];
                        $payment->amount = $v1Payment['amount'];
                        $payment->payment_mode_id = ($paymentMode) ? $paymentMode->id : PaymentMode::bankTransfer()->id;
                        $payment->confirmed = $v1Payment['confirmed'];
                        $payment->rejected_reason = $v1Payment['rejected_reason'];
                        $payment->payment_gateway_id = $v1Payment['card_payment_channel_id'];
                        $payment->reference = $v1Payment['reference'];
                        $payment->success = $v1Payment['success'];
                        $payment->failure_message = $v1Payment['failure_message'];
                        $payment->flag = $v1Payment['flag'];
                        $payment->flag_message = $v1Payment['flag_message'];
                        $payment->bank_account_id = ($bankAccount) ? $bankAccount->id : null;
                        $payment->payment_date = $v1Payment['payment_date'];
                        $payment->purpose = ($order->is_installment == 1) ? PaymentPurpose::INSTALLMENT_PAYMENT->value : PaymentPurpose::PACKAGE_FULL_PAYMENT->value;
                        // $payment->installment_number = ;
                        $payment->user_id = ($user) ? $user->id : null;
                        $payment->created_at = $v1Payment['created_at'];
                        $payment->updated_at = $v1Payment['updated_at'];
                        $payment->migrated = true;
                        $payment->save();

                        $evidenceFile = null;
                        if($v1Payment['evidence_file_id']) $evidenceFile = $this->getFile($v1Payment['evidence_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_EVIDENCE->value);

                        if(isset($v1Payment['receipt_file_id']) && $v1Payment['receipt_file_id']) {
                            $receiptFile = $this->getFile($v1Payment['receipt_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_RECEIPT->value);
                            $payment->receipt_file_id = $receiptFile->id;
                        }
                        if($evidenceFile) $payment->evidence_file_id = $evidenceFile->id;
                        $payment->update();

                        Utilities::logSuccessMigration("Payment Migration Successful.. PaymentId: ".$payment->id);
                    }else{
                        Utilities::logFailedMigration("Payment not Migrated.. Client not found V1PaymentId: ".$v1Payment['id']);
                    }
                }
            }
        });
        $this->markAsMigrated($this->paymentsMigration);
    }

    private function migrateOrderClientPackages($v1Order, $order)
    {
        $customerPackage = DB::connection('db1')->table('customer_packages')->where("purchase_id", $v1Order['id'])->where("purchase_type", "LIKE", "%Order%")->first();
        if($customerPackage) {
            $customerPackage = (array) $customerPackage;
            $client = $this->getClient($customerPackage['customer_id']);
            if($client) {
                $clientPackage = new ClientPackage;
                $clientPackage->client_id = $client->id;
                $clientPackage->package_id = $order->package_id;
                $clientPackage->amount = $order->amount_payable;
                $clientPackage->units = $order->units;
                $clientPackage->unit_price = $order->unit_price;
                $clientPackage->sold = $customerPackage['sold'];
                $clientPackage->origin = ClientPackageOrigin::ORDER->value;
                $clientPackage->purchase_complete = $order->completed;
                $clientPackage->purchase_completed_at = $customerPackage['updated_at'];
                $clientPackage->purchase_type = Order::$type;
                $clientPackage->purchase_id = $order->id;
                $clientPackage->created_at = $customerPackage['created_at'];
                $clientPackage->updated_at = $customerPackage['updated_at'];
                $clientPackage->migrated = true;

                $clientPackage->save();

                $contractFile = $this->getFile($customerPackage['contract_file_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::CONTRACT->value);
                $doaFile = $this->getFile($customerPackage['doa_file_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::DEED_OF_ASSIGNMENT->value);
                $happinessFile = $this->getFile($v1Order['happiness_letter_id'], ['id'=>$order->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::LETTER_OF_HAPPINESS->value);

                if($contractFile) $clientPackage->contract_file_id = $contractFile->id;
                if($doaFile) $clientPackage->doa_file_id = $doaFile->id;
                if($happinessFile) $clientPackage->happiness_letter_file_id = $happinessFile->id;
                $clientPackage->update();

                Utilities::logSuccessMigration("Order Client Package Migration Successful.. CustomerPackageId: ".$customerPackage['id']);

                $this->migrateOrderOffers($customerPackage, $clientPackage);
            }else{
                Utilities::logFailedMigration("Order Client Package not Migrated.. Client not found CustomerPackageId: ".$customerPackage['id']);
            }
        }else{
            Utilities::logFailedMigration("Order Client Package not Migrated.. Customer Package not found PurchaseId: ".$v1Order['id']); 
            // throw("Customer Package not found");
        }
    }

    /*
        Migrate offers that whose asset is acquired via an order
    */
    private function migrateOrderOffers($customerPackage, $clientPackage)
    {
        $v1Offers = DB::connection('db1')->table('offers')->where("customer_package_id", $customerPackage['id'])->get();
        if($v1Offers->count() > 0) {
            foreach($v1Offers as $v1Offer) {
                $v1Offer = (array) $v1Offer;
                $client = $this->getClient($v1Offer['customer_id']);
                $user = ($v1Offer['user_id']) ? $this->getUser($v1Offer['user_id']) : null;
                $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                if($client) {
                    $offer = new Offer;
                    $offer->client_id = $client->id;
                    $offer->package_id = $clientPackage->package_id;
                    $offer->client_package_id = $clientPackage->id;
                    $offer->units = $v1Offer['units'];
                    $offer->project_id = $clientPackage->package->project->id;
                    $offer->price = $v1Offer['price'];
                    $offer->package_price = $clientPackage->package->amount;
                    // $offer->resell_order_id = ;
                    // $offer->accepted_bid_id = ;
                    $offer->active = $v1Offer['active'];
                    $offer->approved = $v1Offer['approved'];
                    $offer->rejected_reason = $v1Offer['rejected_reason'];
                    $offer->completed = $v1Offer['completed'];
                    $offer->payment_status_id = ($paymentStatus) ? $paymentStatus->id : null;
                    $offer->user_id = ($user) ? $user->id : null;
                    $offer->approval_date = $v1Offer['updated_at'];
                    $offer->created_at = $v1Offer['created_at'];
                    $offer->updated_at = $v1Offer['updated_at'];
                    $offer->migrated = true;
                    $offer->save();

                    Utilities::logSuccessMigration("Order Offer Migration Successful.. OfferId: ".$offer->id);

                    $this->migrateOfferClientPackages($v1Offer, $offer);
                    $this->migrateOfferPayments($v1Offer, $offer);
                    $this->migrateOfferBids($v1Offer, $offer);
                }else{
                    Utilities::logFailedMigration("Order Offer not Migrated.. Client not found V1OfferId: ".$v1Offer['id']);
                }
            }
        }
    }

    /*
        Migrate client packages that is generated from offer
    */
    private function migrateOfferClientPackages($v1Offer, $offer)
    {
        $customerPackage = DB::connection('db1')->table('customer_packages')->where("purchase_id", $v1Offer['id'])->where("purchase_type", "LIKE", "%".Offer::$type."%")->first();
        if($customerPackage) {
            $customerPackage = (array) $customerPackage;
            $client = $this->getClient($customerPackage['customer_id']);
            if($client) {
                $clientPackage = new ClientPackage;
                $clientPackage->client_id = $client->id;
                $clientPackage->package_id = $offer->package_id;
                $clientPackage->amount = $offer->price;
                $clientPackage->units = $offer->units;
                $clientPackage->unit_price = $offer->unit_price;
                $clientPackage->sold = $customerPackage['sold'];
                $clientPackage->origin = ClientPackageOrigin::OFFER->value;
                $clientPackage->purchase_complete = 1;
                $clientPackage->purchase_completed_at = $customerPackage['updated_at'];
                $clientPackage->purchase_type = Offer::$type;
                $clientPackage->purchase_id = $offer->id;
                $clientPackage->created_at = $customerPackage['created_at'];
                $clientPackage->updated_at = $customerPackage['updated_at'];
                $clientPackage->migrated = true;

                $clientPackage->save();

                $contractFile = $this->getFile($customerPackage['contract_file_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::CONTRACT->value);
                $doaFile = $this->getFile($customerPackage['doa_file_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::DEED_OF_ASSIGNMENT->value);
                $happinessFile = $this->getFile($v1Offer['happiness_letter_id'], ['id'=>$offer->client_id, 'type'=>Client::$userType], ['id'=>$clientPackage->id, 'type'=>ClientPackage::$type], FilePurpose::LETTER_OF_HAPPINESS->value);

                if($contractFile) $clientPackage->contract_file_id = $contractFile->id;
                if($doaFile) $clientPackage->doa_file_id = $doaFile->id;
                if($happinessFile) $clientPackage->happiness_letter_file_id = $happinessFile->id;
                $clientPackage->update();

                Utilities::logSuccessMigration("Offer Client Package Migration Successful.. CustomerPackageId: ".$customerPackage['id']);

                $this->migrateOfferOffers($customerPackage, $clientPackage);
            }else{
                Utilities::logFailedMigration("Order Client Package not Migrated.. Customer Package not found PurchaseId: ".$v1Offer['id']); 
            }
        }
    }

    /*
        Migrate offers that whose asset is acquired via an offer
    */
    private function migrateOfferOffers($customerPackage, $clientPackage)
    {
        $v1Offers = DB::connection('db1')->table('offers')->where("customer_package_id", $customerPackage['id'])->get();
        if($v1Offers->count() > 0) {
            foreach($v1Offers as $v1Offer) {
                $client = $this->getClient($v1Offer['customer_id']);
                $user = $this->getUser($customerPackage['user_id']);
                $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                if($client) {
                    $offer = new Offer;
                    $offer->client_id = $client->id;
                    $offer->package_id = $clientPackage->package_id;
                    $offer->client_package_id = $clientPackage->id;
                    $offer->units = $customerPackage['units'];
                    $offer->project_id = $clientPackage->package->project->id;
                    $offer->price = $customerPackage['price'];
                    $offer->package_price = $clientPackage->package->amount;
                    // $offer->resell_order_id = ;
                    // $offer->accepted_bid_id = ;
                    $offer->active = $customerPackage['active'];
                    $offer->approved = $customerPackage['approved'];
                    $offer->rejected_reason = $customerPackage['rejected_reason'];
                    $offer->completed = $customerPackage['completed'];
                    $offer->payment_status_id = ($paymentStatus) ? $paymentStatus->id : null;
                    $offer->user_id = ($user) ? $user->id : null;
                    $offer->approval_date = $customerPackage['updated_at'];
                    $offer->created_at = $customerPackage['created_at'];
                    $offer->updated_at = $customerPackage['updated_at'];
                    $offer->migrated = true;
                    $offer->save();

                    $this->migrateOfferPayments($v1Offer, $offer);

                    $this->migrateOfferBids($v1Offer, $offer);

                    Utilities::logSuccessMigration("Offer Offer Migration Successful.. OfferId: ".$offer->id);
                }else{
                    Utilities::logFailedMigration("Offer Offer not Migrated.. Client not found V1OfferId: ".$v1Offer['id']);
                }
            }
        }
    }

    private function migratePackagePhotos($v1Package, $package, $user)
    {
        DB::connection('db1')->table('package_photos')->where("package_id", $v1Package['id'])->orderBy('id')->chunk(500, function ($records) use($package, $user) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $packagePhoto = (array) $record;
                    $photo = DB::connection('db1')->table('files')->where("id", $packagePhoto['file_id'])->first();
                    if($photo) $photo = (array) $photo;

                    $media = new PackageMedia;
                    $media->package_id = $package->id;
                    $media->file_id = 1;
                    $media->created_at = $packagePhoto['created_at'];
                    $media->updated_at = $packagePhoto['updated_at'];
                    $media->migrated = true;
                    $media->save();

                    $file = ($photo) ? $this->migrateFile($photo, ['id' => $user->id, 'type' => User::$userType], ['id'=>$media->id, 'type'=>PackageMedia::$type], FilePurpose::PACKAGE_PHOTO) : null;
                    if($file) {
                        $media->file_id = $file->id;
                        $media->update();
                    }
                    Utilities::logSuccessMigration("Package Media Migration.. MediaId: ".$media->id);
                }
            }
        });
        $this->markAsMigrated($this->packagePhotosMigration);
    }

    private function migrateOfferPayments($v1Offer, $offer)
    {
        DB::connection('db1')->table('sales_offer_payments')->where("offer_id", $v1Offer['id'])->orderBy('id')->chunk(500, function ($records) use($v1Offer, $offer) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Payment = (array) $record;
                    $paymentMode = DB::connection('db1')->table('payment_modes')->where("id", $v1Payment['payment_mode_id'])->first();
                    if($paymentMode) {
                        $paymentMode = (array) $paymentMode;
                        $paymentMode = PaymentMode::where('name', 'LIKE', '%'.$paymentMode['name'].'%')->first();
                    }
                    $bankAccount = ($v1Payment['bank_account_id']) ? $this->getBankAccount($v1Payment['bank_account_id']) : null;
                    $user = ($v1Payment['user_id']) ? $this->getUser($v1Payment['user_id']) : null;
                    $client = $this->getClient($v1Offer['customer_id']);

                    if($client) {
                        $payment = new Payment;
                        $payment->client_id = $client->id;
                        $payment->purchase_id = $offer->id;
                        $payment->purchase_type = Offer::$type;
                        $payment->receipt_no = $v1Payment['receipt_no'];
                        $payment->amount = $v1Payment['amount'];
                        $payment->payment_mode_id = ($paymentMode) ? $paymentMode->id : PaymentMode::bankTransfer()->id;
                        $payment->confirmed = $v1Payment['confirmed'];
                        $payment->rejected_reason = $v1Payment['rejected_reason'];
                        $payment->payment_gateway_id = $v1Payment['card_payment_channel_id'];
                        $payment->reference = $v1Payment['reference'];
                        $payment->success = $v1Payment['success'];
                        $payment->failure_message = $v1Payment['failure_message'];
                        $payment->flag = $v1Payment['flag'];
                        $payment->flag_message = $v1Payment['flag_message'];
                        $payment->bank_account_id = ($bankAccount) ? $bankAccount->id : null;
                        $payment->payment_date = $v1Payment['payment_date'];
                        $payment->purpose = PaymentPurpose::OFFER_PAYMENT->value;
                        // $payment->installment_number = ;
                        $payment->user_id = ($user) ? $user->id : null;
                        $payment->created_at = $v1Payment['created_at'];
                        $payment->updated_at = $v1Payment['updated_at'];
                        $payment->migrated = true;
                        $payment->save();

                        $evidenceFile = null;
                        if($v1Payment['evidence_file_id']) $evidenceFile = $this->getFile($v1Payment['evidence_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_EVIDENCE->value);

                        if(isset($v1Payment['receipt_file_id']) && $v1Payment['receipt_file_id']) {
                            $receiptFile = $this->getFile($v1Payment['receipt_file_id'], ['id'=>$client->id, 'type'=>Client::$userType], ['id'=>$payment->id, 'type'=>Payment::$type], FilePurpose::PAYMENT_RECEIPT->value);
                            $payment->receipt_file_id = $receiptFile->id;
                        }
                        if($evidenceFile) $payment->evidence_file_id = $evidenceFile->id;
                        $payment->update();

                        Utilities::logSuccessMigration("Offer Payment Migration Successful.. PaymentId: ".$payment->id);
                    }else{
                        Utilities::logFailedMigration("Offer Payment not Migrated.. Client not found SalesOfferPaymentId: ".$v1Payment['id']);
                    }
                }
            }
        });
        $this->markAsMigrated($this->paymentsMigration);
    }

    private function migrateOfferBids($v1Offer, $offer)
    {
        DB::connection('db1')->table('offer_bids')->where("offer_id", $v1Offer['id'])->orderBy('id')->chunk(500, function ($records) use($v1Offer, $offer) {
            if(count($records) > 0) {
                foreach ($records as $record) {
                    $v1Bid = (array) $record;
                    $client = $this->getClient($v1Offer['customer_id']);
                    $paymentStatus = $this->getPaymentStatus($v1Offer['payment_status_id']);
                    if(!$paymentStatus) $paymentStatus = PaymentStatus::pending();

                    if($client) {
                        $bid = new OfferBid;
                        $bid->client_id = $client->id;
                        $bid->offer_id = $offer->id;
                        $bid->price = $v1Bid['bid_price'];
                        $bid->accepted = $v1Bid['accepted'];
                        $bid->cancelled = $v1Bid['cancelled'];
                        $bid->payment_status_id = $paymentStatus->id;
                        $bid->created_at = $v1Bid['created_at'];
                        $bid->updated_at = $v1Bid['updated_at'];
                        $bid->migrated = true;
                        $bid->save();

                        Utilities::logSuccessMigration("Offer Bid Migration Successful.. BidId: ".$bid->id);
                    }else{
                        Utilities::logFailedMigration("Offer Bid not Migrated.. Client not found V1BidId: ".$v1Bid['id']);
                    }
                }
            }
        });
    }

    public function siteTours()
    {
        // dd('dont run');
        // Fetch from v1 in chunks (to handle large data)
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('inspection_days')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        // Convert to array
                        $inspectionDay = (array) $record;
                        $monthlyWeekDay = DB::connection('db1')->table('monthly_week_days')->where("id", $inspectionDay['monthly_week_day_id'])->first();
                        $project = $this->getProjectFromProjectLocation($inspectionDay['project_location_id']);

                        if($monthlyWeekDay && $project) {
                            $monthlyWeekDay = (array) $monthlyWeekDay;
                            // Use Eloquent model for v2
                            $schedule = new SiteTourSchedule;
                            $schedule->project_type_id = $project->project_type_id;
                            $schedule->project_id = $project->id;
                            // $schedule->package_id = $data['lastname'];
                            if($monthlyWeekDay['custom_date']) {
                                $schedule->available_date = $monthlyWeekDay['custom_date'];
                            }else{
                                $schedule->recurrent = 1;
                                $schedule->recurrent_day = ucfirst($monthlyWeekDay['day_name']);
                            }
                            $schedule->available_time = $inspectionDay['time'];
                            $schedule->slots = 20;
                            $schedule->fee = 5000;
                            $schedule->created_at = $inspectionDay['created_at'];
                            $schedule->updated_at = $inspectionDay['updated_at'];
                            $schedule->migrated = true;

                            $schedule->save();

                            Utilities::logSuccessMigration("Site Tour Schedule Migration Successful.. ScheduleId: ".$schedule->id);

                            $this->migrateSiteTourBooking($inspectionDay, $schedule);
                        }
                    }
                }
            });
            $this->markAsMigrated($this->monthlyWeekDaysMigration);
            $this->markAsMigrated($this->inspectionDaysMigration);
            $this->markAsMigrated($this->inspectionRequestsMigration);
            DB::commit();
            // return response()->json(['message' => 'Site Tour Schedules Attempts copied successfully!']);
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
    }

    private function migrateSiteTourBooking($inspectionDay, $schedule)
    {
        $requests = DB::connection('db1')->table('inspection_requests')->where("inspection_day_id", $inspectionDay['id'])->get();
        if($requests->count() > 0) {
            foreach($requests as $request) {
                $request = (array) $request;
                $booked = SiteTourBookedSchedule::where("booked_date", $request['proposed_date'])->first();
                if($booked) {
                    $booked->total = $booked->total + 1;
                    $booked->save();
                }else{
                    $booked = new SiteTourBookedSchedule;
                    $booked->site_tour_schedule_id = $schedule->id;
                    $booked->booked_date = $request['proposed_date'];
                    $booked->total = 1;
                    $booked->created_at = $request['created_at'];
                    $booked->updated_at = $request['updated_at'];
                    $booked->migrated = true;
                    $booked->save();

                    Utilities::logSuccessMigration("Booked Site Tour Migration Successful.. BookedId: ".$booked->id);
                }
                $client = null;
                if($request['customer_id']) $client = $this->getClient($request['customer_id']);

                $booking = new SiteTourBooking;
                $booking->booked_schedules_id = $booked->id;
                if($client) $booking->client_id = $client->id;
                $booking->firstname = $request['firstname'];
                $booking->lastname = $request['lastname'];
                $booking->email = $request['email'];
                if($request['phone_number']) $booking->phone_number = $request['phone_number'];
                $booking->created_at = $request['created_at'];
                $booking->updated_at = $request['updated_at'];
                $booking->migrated = true;
                $booking->save();

                Utilities::logSuccessMigration("Site Tour Booking Migration Successful.. BookingId: ".$booking->id);
            }
        }
    }

    private function userCommissions()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('user_commissions')->where("created_at", ">", "2025-10-01")->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $v1Commission = (array) $record;
                        $user = $this->getUser($v1Commission['user_id']);

                        $order = null;
                        $client = null;
                        $v1Order = DB::connection('db1')->table('orders')->where("id", $v1Commission['order_id'])->first();
                        if($v1Order) {
                            $v1Order = (array) $v1Order;
                            $client = $this->getClient($v1Order['customer_id']);
                            $package = $this->getPackageFromPackageItem($v1Order['package_item_id']);
                            //packageId = 265  $clientId = 909 orderDate = 2024-02-15 createdAt = 2024-02-15 08:58:41 
                            //updatedAt = 2023-03-08 09:59:53
                            if($client && $package) {
                                $order = Order::where("migrated", true)->where("client_id", $client->id)
                                            ->where("package_id", $package->id)->where("order_date", $v1Order['order_date'])
                                            ->where("created_at", $v1Order['created_at'])
                                            ->first();
                            }
                        }
                        if($client && !$order) {
                            // dd($v1Order);
                            // dd($v1Order, $client, $package);
                            // dd($package);
                        }
                        // dd($order);
                        if($user && $client) {
                            $this->userCommissionPayments($v1Commission['user_id'], $v1Commission['created_at']);

                            $commission = new StaffCommissionEarning;
                            $commission->user_id = $user->id;
                            $commission->order_id = $order->id;
                            $commission->amount = $v1Commission['order_amount'];
                            $commission->commission = $v1Commission['commission'];
                            $commission->commission_amount = $v1Commission['commission_before_tax'];
                            $commission->tax = $v1Commission['tax'];
                            $commission->commission_after_tax = $v1Commission['commission_amount'];
                            $commission->type = $v1Commission['commission_type'];
                            $commission->created_at = $v1Commission['created_at'];
                            $commission->updated_at = $v1Commission['updated_at'];
                            $commission->migrated = true;
                            $commission->save();

                            Utilities::logSuccessMigration("User Commission Earning Migration Successful.. CommissionId: ".$commission->id);

                            $latestTransaction = $this->latestUserCommissionTransaction($user->id);
                            $transaction = new StaffCommissionTransaction;
                            $transaction->user_id = $user->id;
                            $transaction->transaction_id = $commission->id;
                            $transaction->transaction_type = StaffCommissionEarning::$type;
                            $transaction->balance = ($latestTransaction) ? $latestTransaction->balance + $commission->commission_after_tax : $commission->commission_after_tax;
                            $transaction->created_at = $v1Commission['created_at'];
                            $transaction->updated_at = $v1Commission['updated_at'];
                            $transaction->migrated = true;
                            $transaction->save();

                            Utilities::logSuccessMigration("User Commission Transaction Migration Successful.. TransactionId: ".$transaction->id);
                        }else{
                            Utilities::logFailedMigration("User Commission Earning not Migrated.. Client not found V1CommissionId: ".$v1Commission['id']);
                        }

                    }
                }
            });
            DB::commit();
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
        $this->markAsMigrated($this->userCommissionsMigration);
        $this->markAsMigrated($this->userCommissionPaymentsMigration);
    }

    private function userCommissionPayments($userId, $date)
    {
        $user = $this->getUser($userId);
        // if($date == '2023-03-16 07:10:41') dd($date);
        $commissionPayments = DB::connection('db1')->table('user_commission_payments')
            ->where("user_id", $userId)->where('created_at', '<', $date)
            ->orderBy('id')->get();

        //     $sql = $commissionPayments->toSql();
        //     $bindings = $commissionPayments->getBindings();

        //     dd($sql, $bindings);
        //     dd($commissionPayments);

        // if($date == '2023-03-16 07:10:41') dd($commissionPayments);
                
        if($commissionPayments->count() > 0) {
            foreach ($commissionPayments as $record) {
                $commissionPayment = (array) $record;
                $redemption = new StaffCommissionRedemption;
                $redemption->user_id = $user->id;
                $redemption->amount = $commissionPayment['amount'];
                $redemption->status = RedemptionStatus::COMPLETED->value;
                $redemption->created_at = $commissionPayment['created_at'];
                $redemption->updated_at = $commissionPayment['updated_at'];
                $redemption->migrated = true;
                $redemption->save();

                Utilities::logSuccessMigration("User Commission Redemption Migration Successful.. RedemptionId: ".$redemption->id);

                $latestTransaction = $this->latestUserCommissionTransaction($userId);
                $transaction = new StaffCommissionTransaction;
                $transaction->user_id = $user->id;
                $transaction->transaction_id = $redemption->id;
                $transaction->transaction_type = StaffCommissionRedemption::$type;
                $transaction->balance = ($latestTransaction) ? $latestTransaction->balance - $redemption->amount : 0;
                $transaction->created_at = $commissionPayment['created_at'];
                $transaction->updated_at = $commissionPayment['updated_at'];
                $transaction->migrated = true;
                $transaction->save();

                Utilities::logSuccessMigration("User Commission Transaction Migration Successful.. TransactionId: ".$transaction->id);
            }
        }
    }

    private function latestUserCommissionTransaction($userId)
    {
        return StaffCommissionTransaction::where("user_id", $userId)->orderBy("created_at", "DESC")->first();
    }

    private function promos()
    {
        try{
            DB::beginTransaction();
            DB::connection('db1')->table('promos')->orderBy('id')->chunk(500, function ($records) {
                if(count($records) > 0) {
                    foreach ($records as $record) {
                        $v1Promo = (array) $record;
                        $user = $this->getUser($v1Promo['user_id']);
                        if($v1Promo['user_id'] > 0) {
                            $promo = new Promo;
                            $promo->title = $v1Promo['title'];
                            $promo->discount = $v1Promo['discount'];
                            $promo->start = $v1Promo['start'];
                            $promo->end = $v1Promo['end'];
                            $promo->active = $v1Promo['active'];
                            $promo->description = $v1Promo['description'];
                            $promo->title = $v1Promo['title'];
                            $promo->user_id = $user->id;
                            $promo->created_at = $v1Promo['created_at'];
                            $promo->updated_at = $v1Promo['updated_at'];
                            $promo->migrated = true;
                            $promo->save();

                            Utilities::logSuccessMigration("Promo Migration Successful.. PromoId: ".$promo->id);

                            $this->migratePromoProducts($v1Promo, $promo);
                        }
                    }
                }
            });
            DB::commit();
        }catch(\Exception $e) {
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request');
        }
        $this->markAsMigrated($this->promosMigration);
        $this->markAsMigrated($this->promoProductsMigration);
    }

    private function migratePromoProducts($v1Promo, $promo)
    {
        $v1PromoProducts = DB::connection('db1')->table('promo_products')->where("promo_id", $v1Promo['id'])->get();
        if($v1PromoProducts->count() > 0) {
            foreach($v1PromoProducts as $v1PromoProduct) {
                $v1PromoProduct = (array) $v1PromoProduct;
                if($v1PromoProduct['product_type'] || $v1PromoProduct['product_id']) {
                    if($v1PromoProduct['product_type'] == 'App\Models\Project_location') {
                        $product = $this->getProjectFromProjectLocation($v1PromoProduct['product_id']);
                        $productType = Project::$type;
                        if(!$product) dd($v1PromoProduct['product_id']);
                        $this->savePromoProduct($promo, $product, $productType, $v1PromoProduct);
                    }else{
                        $products = $this->getPackages($v1PromoProduct['product_id']);
                        $productType = Package::$type;
                        if($products->count() > 0) {
                            foreach($products as $product) $this->savePromoProduct($promo, $product, $productType, $v1PromoProduct);
                        }else{
                            dd($v1PromoProduct['product_id']);
                        }
                    }
                }else{
                    Utilities::logFailedMigration("PromoProduct not Migrated.. product_type not found V1PromoProductId: ".$v1PromoProduct['id']);
                }
            }
        }
    }

    private function savePromoProduct($promo, $product, $productType, $v1PromoProduct)
    {
        $promoProduct = new PromoProduct;
        $promoProduct->promo_id = $promo->id;
        $promoProduct->product_id = $product->id;
        $promoProduct->product_type = $productType;
        $promoProduct->created_at = $v1PromoProduct['created_at'];
        $promoProduct->updated_at = $v1PromoProduct['updated_at'];
        $promoProduct->migrated = true;
        $promoProduct->save();

        Utilities::logSuccessMigration("PromoProduct Migration Successful.. PromoProductId: ".$promoProduct->id);
    }

    private function migrateFile($record, $user, $belongs, $purpose)
    {
        $file = new File;
        $file->user_id = $user['id'];
        $file->user_type = $user['type'];
        $file->file_type = $record['file_type'];
        $file->mime_type = $record['mime_type'];
        $file->filename = $record['filename'];
        $file->original_filename = $record['original_filename'];
        $file->extension = $record['extension'];
        $file->size = $record['size'];
        $file->formatted_size = $record['formatted_size'];
        $file->url = $record['url'];
        $file->belongs_id = $belongs['id'];
        $file->belongs_type = $belongs['type'];
        $file->purpose = $purpose;
        $file->public_id = $record['public_id'];
        $file->width = $record['width'];
        $file->height = $record['height'];
        $file->migrated = true;
        $file->save();

        return $file;
    }

    private function getFile($fileId, $user, $belongs, $purpose)
    {
        $v1File = DB::connection('db1')->table('files')->where("id", $fileId)->first();
        $file = null;
        if($v1File) {
            $v1File = (array) $v1File;
            $file = $this->migrateFile($v1File, $user, $belongs, $purpose);
        }
        return $file;
    }

    // private function getPackage($packageItemId)
    // {
    //     $packageItem = DB::connection('db1')->table('package_items')->where("id", $packageItemId)->first();
    //     if($packageItem) {
    //         $packageItem = (array) $packageItem;
    //         $package = DB::connection('db1')->table('packages')->where("id", $packageItem['package_id'])->first();
    //         if($package) {
    //             $package = (array) $package;
    //             $packageItems = DB::connection('db1')->table('package_items')->where("package_id", $packageItem['package_id'])->get();
    //             $packageName = ($packageItems->count() > 1) ? $package['name']." ".$packageItem['size']."SQM" : $package['name'];
    //             $package = Package
    //         }
    //     }
    // }

    private function getUser($userId)
    {
        $user = DB::connection('db1')->table('users')->where("id", $userId)->first();
        if($user) {
            $userData = (array) $user;
            $user = User::where("email", $userData['email'])->first();
        }
        return $user;
    }

    private function getClient($customerId)
    {
        $customer = DB::connection('db1')->table('customers')->where("id", $customerId)->first();
        $client = null;
        if($customer) {
            $customer = (array) $customer;
            $client = Client::where("email", $customer['email'])->first();
        }
        return $client;
    }

    private function getPackages($packageId)
    {
        $v1Package = DB::connection('db1')->table('packages')->where("id", $packageId)->first();
        $package = null;
        if($v1Package) {
            $v1Package = (array) $v1Package;
            $packages = Package::where("name", "LIKE",  "%".$v1Package['name']."%")->get();
        }
        return $packages;
    }

    private function getProjectFromProjectLocation($projectLocationId)
    {
        $project = null;
        $projectLocation = DB::connection('db1')->table('project_locations')->where("id", $projectLocationId)->first();
        if($projectLocation) {
            $projectLocation = (array) $projectLocation;
            $state = DB::connection('db1')->table('states')->where("id", $projectLocation['state_id'])->first();
            $projectLocations = DB::connection('db1')->table('project_locations')->where("project_id", $projectLocation['project_id'])->get();
            $v1Project = DB::connection('db1')->table('projects')->where("id", $projectLocation['project_id'])->first();
            if($v1Project && $state) {
                $v1Project = (array) $v1Project;
                $state = (array) $state;
                $name = ($projectLocations->count() > 1) ? $v1Project['name']." ".$state['name'] : $v1Project['name'];
                $project = Project::where("name", $name)->first();
            }
        }
        return $project;
    }

    private function getPackageFromPackageItem($packageItemId)
    {
        $package = null;
        $packageItem = DB::connection('db1')->table('package_items')->where("id", $packageItemId)->first();
        if($packageItem) {
            $packageItem = (array) $packageItem;
            $packageItems = DB::connection('db1')->table('package_items')->where("package_id", $packageItem['package_id'])->orderBy("id", "ASC")->get();
            $v1Package = DB::connection('db1')->table('packages')->where("id", $packageItem['package_id'])->first();
            if($v1Package) {
                $v1Package = (array) $v1Package;
                $name = ($packageItems->count() > 1) ? $this->getPackageNameFromPackageItem($packageItem['id'], $v1Package, $packageItems) : $v1Package['name'];
                $package = Package::where("name", $name)->first();
            }
        }
        return $package;
    }

    private function getPackageNameFromPackageItem($packageItemId, $package, $packageItems)
    {
        $name = null;
        $i = 0;
        foreach($packageItems as $packageItem) {
            $i = $i + 1;
            if($packageItem->id == $packageItemId) {
                $name = $package['name'].$i." ".$packageItem->size."SQM";
            }
        }
        return $name;
    }

    private function getBankAccount($accountId)
    {
        $account = DB::connection('db1')->table('bank_accounts')->where("id", $accountId)->first();
        if($account) {
            $account = (array) $account;
            $account = BankAccount::where('name', 'LIKE', '%'.$account['account_name'].'%')->first();
        }
        return $account;
    }

    private function getPaymentStatus($paymentStatusId)
    {
        $paymentStatus = DB::connection('db1')->table('payment_statuses')->where("id", $paymentStatusId)->first();
        if($paymentStatus) {
            $paymentStatus = (array) $paymentStatus;
            $paymentStatus = PaymentStatus::where("name", "LIKE",  "%".$paymentStatus['name']."%")->first();
        }
        return $paymentStatus;
    }

    private function getPreview($content)
    {
        // Split into words
        $words = explode(" ", $content);

        // Take the first 20
        $first20 = array_slice($words, 0, 20);

        // Join them back into a string
        $result = implode(" ", $first20);

        return $result;
    }

    function isValidDate($date, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    function extractDate($createdAt)
    {
        $arr = explode(" ", $createdAt);
        return $arr['0'];
    }


}
