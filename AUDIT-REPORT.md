# লাইভ করার আগে চূড়ান্ত রিপোর্ট

## এই শাখায় সম্পন্ন

- কেন্দ্রীয় `appUrl()`/`redirect()` helper যোগ করা হয়েছে এবং subfolder URL-এর জন্য bootstrap/session path প্রস্তুত করা হয়েছে।
- SMTP provider defaults: Gmail, Zoho এবং custom SMTP রাখা হয়েছে।
- Email Service OFF/SMTP validation gate রাখা হয়েছে।
- Fresh schema-তে CMS, login rate limit, auth challenge, audit log ও email settings রাখা হয়েছে।
- Login 2FA challenge database-এ hash, expiry, attempt limit ও single-use হিসেবে ব্যবহার করা হয়েছে।
- `admin/diagnostics.php` যোগ করা হয়েছে: PHP, extensions, database, session, writable paths এবং email status পরীক্ষা করে।
- Relative Apache rewrite rules ও logout portability আপডেট করা হয়েছে।

## যা GitHub থেকে যাচাই করা যায়নি

PHP runtime, MySQL, Apache/Nginx rewrite engine, filesystem permissions এবং Gmail/Zoho SMTP এখানে চালানো সম্ভব নয়। তাই syntax check, fresh install, upgrade migration, SMTP delivery এবং browser flow live-tested নয়।

## লাইভ করার আগে বাধ্যতামূলক ধাপ

1. Target hosting-এ staging copy তৈরি করুন।
2. PHP 8+, PDO MySQL, OpenSSL, JSON, fileinfo, mbstring ও sessions সক্রিয় করুন।
3. Empty database দিয়ে `install.php` browser installation চালান।
4. `admin/diagnostics.php` খুলে সব required check OK করুন।
5. Registration, verification, login, logout, password reset ও 2FA পরীক্ষা করুন।
6. Gmail App Password বা Zoho App Password দিয়ে SMTP test করুন; তারপর Email Service চালু করুন।
7. Domain root এবং subfolder—দুই অবস্থায় clean URL, assets, uploads ও 404 পরীক্ষা করুন।
8. `config.php`, `install.lock`, `database/` ও `uploads/` public access থেকে ব্লক করুন।
9. Production database/files backup নিন।
10. PHP syntax check এবং hosting error log review করুন।

## বাস্তব সিদ্ধান্ত

এই branch source-level deployment-এর জন্য প্রস্তুত করার কাজ সম্পন্ন করেছে, কিন্তু live hosting test ছাড়া production-ready বা bug-free দাবি করা যাবে না। Target hosting-এ উপরোক্ত checklist সফল হলে তবেই সাইট live করুন।
