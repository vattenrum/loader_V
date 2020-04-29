using System;
using System.Collections.Generic;
using System.IO;
using System.Net;
using System.Windows.Forms;
using System.Text.RegularExpressions;
using Microsoft.Win32;
using Authentication;
using Security;
using Utils;

namespace versace_loader
{
    public partial class Login : Form
    {
        bool keep_open = false;
        public Login()
        {
            InitializeComponent();

            this.FormClosing += (s, e) => Environment.Exit(1);

            this.FormClosing += (s, e) => {
                if (!keep_open)
                    Environment.Exit(1);
            };

            // Essentially manually adding accept buttons. (When they press enter, handle the key.)
            flex_password_box.KeyPress += (s, e) => {
                if (e.KeyChar == (char)Keys.Enter)
                    btnLogin_Click(s, e);
            };

            ActiveControl = flex_username_box;
            if (Properties.Settings.Default.username != string.Empty)
            {
                flex_username_box.Text = Properties.Settings.Default.username;
                flex_password_box.Text = Properties.Settings.Default.password;

                flex_remember_me.Checked = true;
                ActiveControl = flex_password_box;
            }
        }

        private void btnLogin_Click(object sender, EventArgs e)
        {
            dynamic result = Networking.Login(flex_username_box.Text, flex_password_box.Text);

            if (result != null)
            {
                if ((string)result.status == "failed")
                {
                    string issue = (string)result.detail;
                    string extra_information = (string)result.extra;
                    switch (issue)
                    {
                        case "connection error":
                        default:
                            unknown_error();
                            return;
                        case "no account":
                        case "wrong password":
                            MessageBox.Show("Incorrect username or password.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                            flex_password_box.ResetText();
                            return;
                        case "sub invalid":
                            MessageBox.Show("Your subscription is invalid or expired.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                            return;
                        case "hwid mismatch":
                            MessageBox.Show("Your PC is not authorized.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                            return;
                        case "banned":
                            MessageBox.Show("Your account has been banned.\nReason: " + extra_information, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                            security_functions.delete_self();
                            Environment.Exit(1);
                            return;
                        case "server offline":
                            MessageBox.Show($"The server is currently disabled.\nReason: {(string)result.reason}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                            Environment.Exit(5);
                            break;
                    }
                }
                else if ((string)result.status == "successful" && (string)result.username == flex_username_box.Text && (int)result.time_left > 0)
                {

                    if (c_utility.get_epoch_time() - (double) result.time > 30)
                    {
                        Environment.Exit(0); //prevent replay attacks
                        return;
                    }

                    if (flex_remember_me.Checked)
                    {
                        Properties.Settings.Default.username = flex_username_box.Text;
                        Properties.Settings.Default.password = flex_password_box.Text;
                        Properties.Settings.Default.Save();
                    }

                    RegistryKey key = Registry.CurrentUser.OpenSubKey("Software", true);

                    key.CreateSubKey("VER$ACE");
                    key = key.OpenSubKey("VER$ACE", true);
                    key.SetValue("username", aes.encrypt_string((string)result.username));

                    MessageBox.Show($"Welcome back, {(string)result.username}!\n This build was created on [BUILD]", "VER$ACE", MessageBoxButtons.OK, MessageBoxIcon.Information);

                    saved_info.username = flex_username_box.Text;
                    saved_info.password = flex_password_box.Text;

                    keep_open = true;
                    Forms.Main form = new Forms.Main(result);
                    form.Show();
                    Hide();
                }
                else {
                    unknown_error();
                }
            }
            else {
                unknown_error();
            }
        }


        private static void unknown_error()
        {
            MessageBox.Show("Something happened.", "VER$ACE", MessageBoxButtons.OK, MessageBoxIcon.Error);
        }
    }
}
