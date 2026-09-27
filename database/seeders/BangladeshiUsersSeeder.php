<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BangladeshiUsersSeeder extends Seeder
{
    private array $maleNames = ['Rahim', 'Karim', 'Hasan', 'Rafi', 'Sakib', 'Nayeem', 'Tanvir', 'Fahim', 'Imran', 'Arafat', 'Shakil', 'Mahin', 'Riad', 'Siam', 'Adnan', 'Tuhin', 'Jahid', 'Nabil', 'Farhan', 'Shuvo'];
    private array $femaleNames = ['Ayesha', 'Nusrat', 'Mim', 'Jannat', 'Sadia', 'Sumaiya', 'Raisa', 'Mahi', 'Tanjila', 'Fariha', 'Anika', 'Mehjabin', 'Sabrina', 'Tasnia', 'Lamisa', 'Puja', 'Mou', 'Ritu', 'Nabila', 'Mithila'];
    private array $lastNames = ['Rahman', 'Hossain', 'Ahmed', 'Islam', 'Khan', 'Chowdhury', 'Akter', 'Sarker', 'Hasan', 'Kabir'];
    private array $locations = ['Dhaka', 'Chattogram', 'Sylhet', 'Rajshahi', 'Khulna', 'Barishal', 'Rangpur', 'Mymensingh', 'Comilla', 'Gazipur'];
    private array $professions = ['Computer Science Student', 'Software Engineer', 'Data Analyst', 'Medical Student', 'Research Assistant', 'UX Designer', 'Cybersecurity Analyst', 'Business Analyst', 'Civil Engineer', 'Lecturer'];
    private array $educations = ['BSc in Computer Science', 'BSc in Engineering', 'MBBS Student', 'BBA in Finance', 'MSc Researcher', 'Bachelor of Architecture', 'BSc in Information Technology', 'Economics Graduate'];
    private array $photoContexts = ['student,computer', 'programmer,computer', 'analyst,computer', 'medical,student', 'researcher,lab', 'designer,computer', 'cybersecurity,computer', 'business,office', 'engineer,construction', 'teacher,classroom'];
    private array $postIdeas = [
        ['title' => 'A walk through old Dhaka', 'body' => 'The streets were busy, colourful, and full of stories today.'],
        ['title' => 'Learning something new', 'body' => 'Spent the evening practising, taking notes, and making a little progress.'],
        ['title' => 'Weekend in Bangladesh', 'body' => 'Good food, familiar faces, and a quiet break from the usual routine.'],
        ['title' => 'A small goal for this month', 'body' => 'Trying to stay consistent and celebrate progress instead of waiting for perfection.'],
        ['title' => 'Rainy afternoon thoughts', 'body' => 'The rain changed the whole mood of the city. Some days are meant to move slowly.'],
    ];
    private array $storyIdeas = [
        'Good morning from Bangladesh. Hope your day starts well.',
        'A quick update from a busy day of learning and work.',
        'Found a beautiful corner of the city today.',
        'Taking a short break and enjoying the little things.',
        'Sharing a bit of positive energy before the day ends.',
    ];

    public function run(): void
    {
        foreach (['male' => $this->maleNames, 'female' => $this->femaleNames] as $gender => $names) {
            foreach (range(1, 50) as $number) {
                $index = $number - 1;
                $firstName = $names[$index % count($names)];
                $lastName = $this->lastNames[($index + ($gender === 'female' ? 3 : 0)) % count($this->lastNames)];
                $name = $firstName.' '.$lastName;
                $email = 'bd-'.$gender.'-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'@example.com';
                $birthday = now()->subYears(18 + ($index % 7))->subDays(($index * 37) % 365)->toDateString();
                $username = Str::slug($firstName.'-'.$lastName).'-bd-'.$gender.'-'.$number;
                $photoNumber = (($index + 1) % 99) ?: 99;
                $photoGender = $gender === 'male' ? 'men' : 'women';
                $photo = "https://randomuser.me/api/portraits/{$photoGender}/{$photoNumber}.jpg";

                $user = User::firstOrNew(['email' => $email]);
                $user->forceFill([
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'bio' => "Hi, I'm {$firstName} from Bangladesh. I enjoy learning, solving meaningful problems, and building a better future through technology and education.",
                    'profession' => $this->professions[$index % count($this->professions)],
                    'location' => $this->locations[$index % count($this->locations)].', Bangladesh',
                    'interests' => 'Technology, science, entrepreneurship, books',
                    'hobbies' => 'Reading, coding, chess, photography',
                    'profile_photo' => $photo,
                    'status' => 'ACTIVE',
                    'locale' => 'en',
                    'timezone' => 'Asia/Dhaka',
                    'phone' => '+88017'.str_pad((string) (10000000 + ($index + 1) + ($gender === 'female' ? 50 : 0)), 8, '0', STR_PAD_LEFT),
                ])->save();

                Profile::updateOrCreate(['user_id' => $user->id], [
                    'username' => $username,
                    'gender' => ucfirst($gender),
                    'birthday' => $birthday,
                    'relationship_status' => $index % 3 === 0 ? 'Single' : 'It\'s complicated',
                    'contact_email' => $email,
                    'contact_phone' => $user->phone,
                    'website' => 'https://example.com/'.Str::slug($username),
                    'visibility' => ['bio' => true, 'birthday' => true, 'location' => true, 'workplace' => true, 'education' => true, 'relationship_status' => true, 'contact' => false, 'website' => true],
                ]);

                foreach ([
                    'user_bios' => ['bio' => $user->bio],
                    'user_workplaces' => ['workplace' => $user->profession],
                    'user_education' => ['education' => $this->educations[$index % count($this->educations)]],
                    'user_locations' => ['location' => $user->location],
                    'user_relationships' => ['relationship_status' => $index % 3 === 0 ? 'Single' : 'It\'s complicated'],
                ] as $table => $data) {
                    DB::table($table)->updateOrInsert(['user_id' => $user->id], $data + ['updated_at' => now(), 'created_at' => now()]);
                }

                foreach ($this->postIdeas as $postNumber => $postIdea) {
                    $postNumber++;
                    $post = Post::updateOrCreate(
                        ['user_id' => $user->id, 'name' => "{$firstName}'s {$postIdea['title']}"],
                        [
                            'description' => $postIdea['body'],
                            'image' => "https://picsum.photos/seed/bd-post-{$gender}-{$number}-{$postNumber}/1200/800",
                            'share_token' => Str::random(32),
                            'visibility' => 'PUBLIC',
                            'location' => $this->locations[$index % count($this->locations)].', Bangladesh',
                            'feeling' => ['Happy', 'Excited', 'Grateful', 'Thoughtful'][$postNumber % 4],
                        ],
                    );
                    $publishedAt = now()->subDays(($index * 7 + $postNumber * 11) % 365)->subHours(($index + $postNumber) % 24);
                    $post->forceFill(['created_at' => $publishedAt, 'updated_at' => $publishedAt])->save();
                }

                Story::updateOrCreate(
                    ['seed_key' => "bd-{$gender}-{$number}"],
                    [
                        'user_id' => $user->id,
                        'type' => 'IMAGE',
                        'privacy' => 'PUBLIC',
                        'text' => $this->storyIdeas[$index % count($this->storyIdeas)],
                        'image' => "https://picsum.photos/seed/bd-story-{$gender}-{$number}/800/1100",
                        'expires_at' => now()->addDay(),
                        'created_at' => now()->subHours($index % 18),
                    ],
                );
            }
        }

        $this->seedPostShares();

        $this->command?->info('Seeded 100 Bangladeshi users with profiles, posts, active stories, and timeline shares. Password: password');
    }

    private function seedPostShares(): void
    {
        $users = User::where('email', 'like', 'bd-%@example.com')->orderBy('id')->get();
        $posts = Post::whereIn('user_id', $users->pluck('id'))->orderBy('id')->get();

        if ($users->isEmpty() || $posts->isEmpty()) {
            return;
        }

        foreach ($users as $index => $user) {
            $post = $posts[($index * 7 + 3) % $posts->count()];

            if ($post->user_id === $user->id) {
                $post = $posts[($index * 7 + 4) % $posts->count()];
            }

            DB::table('shares')->updateOrInsert(
                [
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'share_type' => 'TIMELINE',
                ],
                [
                    'created_at' => now()->subDays(($index * 5) % 180),
                    'updated_at' => now()->subDays(($index * 5) % 180),
                ],
            );
        }
    }
}
