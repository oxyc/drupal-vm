## DHCP (default)

By default Drupal VM is assigned an IP automatically via DHCP. This is configured when `vagrant_ip` set to `dhcp` in `default.config.yml` or your `config.yml`.

## Static IP

To use a static IP, change the `vagrant_ip` in `config.yml` to the desires private IP address.

## `vagrant-auto_network` plugin

If you prefer using the `vagrant-auto_network` plugin (`vagrant plugin install vagrant-auto_network`) to assign your IP, set the `vagrant_ip` to `0.0.0.0`.

## Network Route Collision

If you have multiple VM providers running VMs on your computer, and you attempt to use the same IP range in both providers, you'll hit a networking conflict. In this case, the only easy way to restore connectivity is to restart your host machine.
