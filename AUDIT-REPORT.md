# কাজের অবস্থা ও যাচাই

এই শাখায় আগের অডিটের পরবর্তী কাজগুলো সম্পন্ন করা হয়েছে:

- ব্রাউজার-ভিত্তিক SMTP activation gate রাখা হয়েছে; সফল পরীক্ষা ছাড়া Email Service চালু হয় না।
- Gmail, Zoho এবং Custom SMTP provider configuration রাখা হয়েছে।
- Email Service বন্ধ থাকলে রেজিস্ট্রেশন সাইট ভেঙে যায় না; ইমেইল-নির্ভর verification/2FA পরিষ্কারভাবে unavailable থাকে।
- ব্যবহারকারী নিজের dashboard থেকে 2FA চালু/বন্ধ করতে পারে; Email Service বন্ধ থাকলে Enable action disabled।
- 2FA challenge database-এ hash, expiry, attempt limit এবং single-use হিসেবে রাখা হয়েছে।
- নতুন ইনস্টল schema-তে `auth_challenges`, `audit_logs`, categories, menus এবং news table অন্তর্ভুক্ত করা হয়েছে।
- সাবফোল্ডার-ভিত্তিক URL/session path-এর জন্য helper ব্যবহার করা হয়েছে।
- Existing installations-এর জন্য `003_email_service_and_auth_challenges.sql` migration যোগ করা হয়েছে।

## যা লাইভভাবে যাচাই করা হয়নি

GitHub API থেকে PHP runtime, MySQL, Apache rewrite বা SMTP server চালানো যায় না। তাই PHP syntax, fresh installation, Gmail/Zoho connection এবং hosting-specific behavior এখনও target hosting-এ চালিয়ে যাচাই করতে হবে। কোনো live test সফল হয়েছে বলে এই রিপোর্ট দাবি করছে না।
