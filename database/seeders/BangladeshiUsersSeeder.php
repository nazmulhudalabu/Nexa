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

                foreach (range(1, 2) as $postNumber) {
                    $post = Post::updateOrCreate(
                        ['user_id' => $user->id, 'name' => "{$firstName}'s Bangladesh diary {$postNumber}"],
                        [
                            'description' => $postNumber === 1 ? 'Sharing a little moment from everyday life in Bangladesh.' : 'Learning, growing, and enjoying the small moments with good people.',
                            'share_token' => Str::random(32),
                        ],
                    );
                    $post->forceFill(['created_at' => now()->subDays(($index * 3) + $postNumber), 'updated_at' => now()->subDays(($index * 3) + $postNumber)])->save();
                }

                Story::updateOrCreate(
                    ['seed_key' => "bd-{$gender}-{$number}"],
                    [
                        'user_id' => $user->id,
                        'text' => $postNumber === 1 ? 'A fresh day in Bangladesh.' : 'Making memories and sharing good energy.',
                        'image' => "https://picsum.photos/seed/bd-story-{$gender}-{$number}/800/1100",
                        'expires_at' => now()->addDay(),
                        'created_at' => now()->subHours($index % 18),
                    ],
                );
            }
        }

        $this->command?->info('Seeded 100 Bangladeshi users: 50 male and 50 female, all aged 18-24. Password: password');
    }
}
